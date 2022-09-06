<?php

class ServiceUser extends Pix_Table
{
    public function init()
    {
        $this->_name = 'service_user';
        $this->_primary = 'id';

        $this->_columns['id'] = ['type' => 'int', 'auto_increment' => true];
        $this->_columns['service_id'] = ['type' => 'int'];
        $this->_columns['user_id'] = ['type' => 'varchar', 'size' => 64];
        $this->_columns['data'] = ['type' => 'jsonb'];

        $this->addIndex('service_user', ['service_id', 'user_id'], 'unique');
    }
}
