<?php

class UserController extends Pix_Controller
{
    public function init()
    {
        if (!$sToken = Pix_Session::get('sToken')) {
            $sToken = crc32(uniqid());
            Pix_Session::set('sToken', $sToken);
        }
        $this->view->sToken = $sToken;
    }

    public function indexAction()
    {
        if (!$login_id = Pix_Session::get('login_id')) {
            return $this->redirect('/');
        }

        if ($user = User::findByLoginID($login_id)) {
            return $this->redirect('/_/user/edit');
        }

        $ids = Pix_Session::get('ids');
        if (!$users = ServiceUser::searchByIds($ids)) {
            return $this->alert('您目前還未有任何成就可以領取，請期待下一版本改版', '/');
        }

        return $this->redirect('/_/user/new');
    }

    public function newAction()
    {
        if (!$login_id = Pix_Session::get('login_id')) {
            return $this->redirect('/');
        }

        if ($user = User::findByLoginID($login_id)) {
            return $this->redirect('/_/user/edit');
        }

        $ids = Pix_Session::get('ids');
        if (!$users = ServiceUser::searchByIds($ids)) {
            return $this->alert('您目前還未有任何成就可以領取，請期待下一版本改版', '/');
        }

        $this->view->login_id = $login_id;
        $this->view->ids = $ids;
        $this->view->prefixs = ServiceUser::getUserIdPrefixByIds($ids);

        if ($_POST) {
            if ($_POST['sToken'] != $this->view->sToken) {
                return $this->alert('stoken error', '/');
            }

            if (strlen($_POST['id']) < 2) {
                return $this->alert('id 太短', '/_/user/new');
            }
            if (strlen($_POST['id']) > 16) {
                return $this->alert('id 太長', '/_/user/new');
            }
            $id = $_POST['id'];
            if ($this->view->prefixs) {
                $prefixs = array_filter($this->view->prefixs, function($s) use ($id){
                    return strpos($id, $s) === 0;
                });
                if (count($prefixs) == 0) {
                    return $this->alert("id 必須以 " . implode(' 或 ', $prefixs) . " 開頭", '/_/user/new/');
                }
            }

            try {
                $d = new StdClass;
                if ($avatar = Pix_Session::get('avatar')) {
                    $d->avatar = $avatar;
                }
                $u = User::insert([
                    'name' => $_POST['id'],
                    'ids' => json_encode($ids),
                    'data' => json_encode($d),
                ]);
                $u->updateServiceUsers();
            } catch (Pix_Table_DuplicateException $e) {
                return $this->alert('代號已經被使用了，請再更換代號', '/_/user/new');
            }

            return $this->alert('建立成功', '/_/user/edit');
        }
    }

    public function editAction()
    {
        if (!$login_id = Pix_Session::get('login_id')) {
            return $this->redirect('/');
        }

        if (!$user = User::findByLoginID($login_id)) {
            return $this->redirect('/_/user/new');
        }

        $this->view->user = $user;
        if ($_POST) {
            if ($_POST['sToken'] != $this->view->sToken) {
                return $this->alert('stoken error', '/_/user/edit');
            }
            $data = json_decode($user->data);
            $data->info = $_POST['info'];
            $user->update(['data' => json_encode($data)]);
            return $this->alert('更新完成', '/_/user/edit');
        }
    }

    public function slackloginAction()
	{
        $client_id = getenv('SLACK_CLIENT_ID');
        $redirect_uri = 'https://' . getenv('SLACK_CALLBACK_HOST') . '/_/user/slackdone';

        $url = sprintf("https://g0v-tw.slack.com/oauth/authorize?client_id=%s&scope=%s&redirect_uri=%s&state=%s&team=%s",
            urlencode($client_id), // client_id
            'identity.basic,identity.avatar,identity.email', // scope
            urlencode($redirect_uri), // redirect_uri
            urlencode($_GET['next']), // state
            "" // team
        );
        return $this->redirect($url);
    }

    public function googleloginAction()
    {
        $return_to = 'https://' . getenv('SLACK_CALLBACK_HOST') . '/_/user/googledone';
        $url = 'https://accounts.google.com/o/oauth2/auth?'
            . '&state=' . urlencode($_GET['next'])
            . '&scope=email'
            . '&redirect_uri=' . urlencode($return_to)
            . '&response_type=code'
            . '&client_id=' . getenv('GOOGLE_CLIENT_ID')
            . '&access_type=offline';
        return $this->redirect($url);
    }

    public function showAction($params)
    {
        $name = $params[0];
        if (!$name) {
            return $this->redirect('/');
        }
        if (!$user = User::find_by_name($name)) {
            return $this->redirect('/');
        }
        $this->view->user = $user;
    }

    public function setavatarAction()
    {
        $avatar = Pix_Session::get('avatar');
        if (!$avatar) {
            return $this->alert('找不到頭像', '/_/user/edit');
        }
        if (!$login_id = Pix_Session::get('login_id')) {
            return $this->redirect('/');
        }

        if (!$user = User::findByLoginID($login_id)) {
            return $this->redirect('/');
        }
        $d = json_decode($user->data);
        $d->avatar = $avatar;
        $user->update(['data' => json_encode($d)]);
        return $this->redirect('/_/user/edit');
    }

	public function slackdoneAction()
	{
        $client_id = getenv('SLACK_CLIENT_ID');
        $client_secret = getenv('SLACK_CLIENT_SECRET');
        $redirect_uri = 'https://' . getenv('SLACK_CALLBACK_HOST') . '/_/user/slackdone';
        if (!$code = $_GET['code']) {
            return $this->alert("Error", '/');
        }

        $url = "https://slack.com/api/oauth.access";
        $url .= "?client_id=" . urlencode($client_id);
        $url .= "&client_secret=" . urlencode($client_secret);
        $url .= "&code=" . urlencode($code);
        $url .= "&redirect_uri=" . urlencode($redirect_uri);
        $obj = json_decode(file_get_contents($url));
        if (!$obj->ok) {
            return $this->alert($obj->error, '/');
        }
        $next = $_GET['state'];
        $access_token = $obj->access_token;
        $user_id = $obj->user_id;
        $url = sprintf('https://slack.com/api/users.identity?token=%s', urlencode($access_token));
        $obj = json_decode(file_get_contents($url));
        if (!$obj->ok) {
            return $this->alert($obj->error, '/');
        }

        Pix_Session::set('login_id', 'slack://' . $obj->user->id);
        Pix_Session::set('login_name', $obj->user->name);
        foreach ([512, 192, 72, 48, 32] as $s) {
            if (property_exists($obj->user, 'image_' . $s)) {
                Pix_Session::set('avatar', $obj->user->{'image_' . $s});
                break;
            }
        }
        $ids = [];
        $ids[] = 'slack://' . $obj->user->id;
        $ids[] = $obj->user->email;
        Pix_Session::set('ids', $ids);
        if ($next) {
            return $this->redirect($next);
        } else {
            return $this->redirect('/_/user/');
        }
    }

	public function googledoneAction()
    {
        $return_to = 'https://' . getenv('SLACK_CALLBACK_HOST') . '/_/user/googledone';
        $next = $_GET['state'];

        $params = array();
        $params[] = 'code=' . urlencode($_GET['code']);
        $params[] = 'client_id=' . urlencode(getenv('GOOGLE_CLIENT_ID'));
        $params[] = 'client_secret=' . urlencode(getenv('GOOGLE_CLIENT_SECRET'));
        $params[] = 'redirect_uri=' . urlencode($return_to);
        $params[] = 'grant_type=authorization_code';
        $curl = curl_init('https://www.googleapis.com/oauth2/v3/token');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, implode('&', $params));
        $obj = json_decode(curl_exec($curl));
        if (!$obj->id_token) {
            return $this->alert('login failed', '/');
        }
        $tokens = explode('.', $obj->id_token);
        $login_info = json_decode(base64_decode($tokens[1]));
        if (!$login_info->email or !$login_info->email_verified) {
            return $this->alert('login failed', '/');
        }
        $email = $login_info->email;
        $access_token = $obj->access_token;

        $curl = curl_init('https://www.googleapis.com/oauth2/v1/userinfo?alt=json');
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer' . $access_token));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($curl);
        $obj = json_decode($content);
        if ($obj->picture) {
            $obj->picture = preg_replace('/=s96-c$/', '=s1000', $obj->picture);
            Pix_Session::set('avatar', $obj->picture);
        } else {
            Pix_Session::set('avatar', '');
        }

        Pix_Session::set('login_id', $email);
        Pix_Session::set('login_name', explode('@', $email)[0]);
        $ids = [];
        $ids[] = $email;
        Pix_Session::set('ids', $ids);
        if ($next) {
            return $this->redirect($next);
        } else {
            return $this->redirect('/_/user/');
        }
    }

    public function logoutAction()
    {
        Pix_Session::delete('login_id');
        Pix_Session::delete('login_name');
        Pix_Session::delete('ids');
        return $this->redirect('/');
    }
}
