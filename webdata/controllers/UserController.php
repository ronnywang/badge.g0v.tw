<?php

class UserController extends Pix_Controller
{
    public function loginAction()
	{
        $client_id = getenv('SLACK_CLIENT_ID');
        $redirect_uri = 'https://' . getenv('SLACK_CALLBACK_HOST') . '/user/slackdone';

        $url = sprintf("https://g0v-tw.slack.com/oauth/authorize?client_id=%s&scope=%s&redirect_uri=%s&state=%s&team=%s",
            urlencode($client_id), // client_id
            'identity.basic,identity.avatar,identity.email', // scope
            urlencode($redirect_uri), // redirect_uri
            urlencode($_GET['next']), // state
            "" // team
        );
        return $this->redirect($url);
    }

	public function slackdoneAction()
	{
        $client_id = getenv('SLACK_CLIENT_ID');
        $client_secret = getenv('SLACK_CLIENT_SECRET');
        $redirect_uri = 'https://' . getenv('SLACK_CALLBACK_HOST') . '/user/slackdone';
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
        return $this->redirect('/');
    }

    public function logoutAction()
    {
        Pix_Session::delete('login_id');
        Pix_Session::delete('login_name');
        Pix_Session::delete('ids');
        return $this->redirect('/');
    }
}
