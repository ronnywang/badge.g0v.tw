<?php

class ServiceBadge extends Pix_Table
{
    public function init()
    {
        $this->_name = 'service_badge';
        $this->_primary = 'id';

        $this->_columns['id'] = ['type' => 'int', 'auto_increment' => true];
        $this->_columns['service_id'] = ['type' => 'int'];
        $this->_columns['service_user'] = ['type' => 'int'];
        $this->_columns['badge_time'] = ['type' => 'int'];
        $this->_columns['badge_hash'] = ['type' => 'bigint'];
        $this->_columns['brief'] = ['type' => 'text'];
        $this->_columns['data'] = ['type' => 'jsonb'];

        $this->addIndex('badgehash', ['service_id', 'service_user', 'badge_time', 'badge_hash'], 'unique');
    }
}
