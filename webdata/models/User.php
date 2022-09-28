<?php

class UserRow extends Pix_Table_Row
{
    public function getData()
    {
        $data = json_decode($this->data);
        if (!property_exists($data, 'info')) {
            $data->info = new StdClass;
        }
        if (!property_exists($data->info, 'name')) {
            $data->info->name = $this->name;
        }
        return $data;
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
