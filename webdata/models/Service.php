<?php

class Service extends Pix_Table
{
    public function init()
    {
        $this->_name = 'service';
        $this->_primary = 'id';

        $this->_columns['id'] = ['type' => 'int', 'auto_increment' => true];
        $this->_columns['service_id'] = ['type' => 'varchar', 'size' => 32];
        $this->_columns['data'] = ['type' => 'jsonb'];

        $this->addIndex('service_id', ['service_id'], 'unique');
    }
}
