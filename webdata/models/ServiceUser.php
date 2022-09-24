<?php

class ServiceUserRow extends Pix_Table_Row
{
    public function getData()
    {
        return json_decode($this->data);
    }
}

class ServiceUser extends Pix_Table
{
    public function init()
    {
        $this->_name = 'service_user';
        $this->_primary = 'id';
        $this->_rowClass = 'ServiceUserRow';

        $this->_columns['id'] = ['type' => 'int', 'auto_increment' => true];
        $this->_columns['service_id'] = ['type' => 'int'];
        $this->_columns['user_id'] = ['type' => 'varchar', 'size' => 64];
        $this->_columns['data'] = ['type' => 'jsonb'];

        $this->_relations['service'] = ['rel' => 'has_one', 'type' => 'Service', 'foreign_key' => 'service_id'];
        $this->_relations['badges'] = ['rel' => 'has_many', 'type' => 'ServiceBadge', 'foreign_key' => 'service_user'];

        $this->addIndex('service_user', ['service_id', 'user_id'], 'unique');
    }

    public static function getUserIdPrefixByIds($ids)
    {
        $users = self::searchByIds($ids);
        if (!$users) {
            return [];
        }

        $names = [];
        foreach ($users as $u) {
            $n = preg_replace('#\s#', '', strtolower($u->getData()->name));
            if ($n) {
                $names[] = $n;
            }
        }
        return array_unique($names);
    }

    public static function searchByIds($ids)
    {
        if (!$ids) {
            return;
        }
        $terms = array_map(function($id) {
            return sprintf("(data->'hash_ids' @> '\"%s\"')", md5($id . 'g0vg0v'));
        }, $ids);

        $users = [];

        foreach (ServiceUser::search(implode(' OR ', $terms)) as $service_user) {
            $users[$service_user->id] = $service_user;
        }

        return array_values($users);
    }
}
