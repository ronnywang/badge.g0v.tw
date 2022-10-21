<?php

class ServiceRow extends Pix_Table_Row
{
    public function getData()
    {
        $data = json_decode($this->data);
        if (!property_exists($data, 'public')) {
            $data->public = true;
        }
        return $data;
    }
}

class Service extends Pix_Table
{
    public function init()
    {
        $this->_name = 'service';
        $this->_primary = 'id';
        $this->_rowClass = 'ServiceRow';

        $this->_columns['id'] = ['type' => 'int', 'auto_increment' => true];
        $this->_columns['service_id'] = ['type' => 'varchar', 'size' => 32];
        $this->_columns['data'] = ['type' => 'jsonb'];

        $this->addIndex('service_id', ['service_id'], 'unique');
    }
}
