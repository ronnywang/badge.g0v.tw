<?php

class UserRow extends Pix_Table_Row
{
    public function updateServiceUsers()
    {
        $d = $this->getData();
        if (!property_exists($d, 'service_users')) {
            $d->service_users = [];
        }
        $ids = json_decode($this->ids);
        foreach (ServiceUser::searchByIds($ids) as $su) {
            $d->service_users[] = $su->id;
        }
        $this->update(['data' => json_encode($d)]);
    }

    public function getData()
    {
        $data = json_decode($this->data);
        if (!property_exists($data, 'info')) {
            $data->info = new StdClass;
        }

        if (!property_exists($data, 'public')) {
            $data->public = new StdClass;
        }

        if (!property_exists($data->info, 'name')) {
            $data->info->name = $this->name;
        }
        return $data;
    }

    public function isServiceUserPublic($service_user)
    {
        $public = $this->getData()->public;
        if (property_exists($public, $service_user->id)) {
            return $public->{$service_user->id};
        }
        return $service_user->service->getData()->public;
    }
}

class User extends Pix_Table
{
    public function init()
    {
        $this->_name = 'user';
        $this->_primary = 'id';
        $this->_rowClass = 'UserRow';

        $this->_columns['id'] = ['type' => 'int', 'auto_increment' => true];
        $this->_columns['name'] = ['type' => 'varchar', 'size' => 16];
        $this->_columns['ids'] = ['type' => 'jsonb'];
        $this->_columns['data'] = ['type' =>'jsonb'];

        $this->addIndex('name', ['name'], 'unique');
    }

    public static function findByLoginID($login_id)
    {
        return User::search(sprintf("(ids @> %s)", User::getDb()->quoteWithColumn('data', json_encode($login_id))))->first();
    }
}
