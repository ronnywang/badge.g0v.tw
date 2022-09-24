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

        $this->addIndex('service_user', ['service_id', 'user_id'], 'unique');
    }

    public static function searchByIds($ids)
    {
        if (!$ids) {
            return;
        }
        $terms = array_map(function($id) {
            return sprintf("(data->'hash_ids' @> '\"%s\"')", md5($id . 'g0vg0v'));
        }, $ids);

        $ret = new StdClass;
        $ret->users = [];
        $ret->badges = [];

        foreach (ServiceUser::search(implode(' OR ', $terms)) as $service_user) {
            $ret->users[$service_user->id] = $service_user;
        }

        if (!count($ret->users)) {
            return;
        }

        foreach (ServiceBadge::search(1)->searchIn('service_user', array_keys($ret->users)) as $badge) {
            $ret->badges[] = $badge;
        }
        $ret->users = array_values($ret->users);
        return $ret;
    }
}
