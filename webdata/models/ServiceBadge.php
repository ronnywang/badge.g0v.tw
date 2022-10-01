<?php

class ServiceBadgeRow extends Pix_Table_Row
{
    public function getData()
    {
        return json_decode($this->data);
    }
}

class ServiceBadge extends Pix_Table
{
    public function init()
    {
        $this->_name = 'service_badge';
        $this->_primary = 'id';
        $this->_rowClass = 'ServiceBadgeRow';

        $this->_columns['id'] = ['type' => 'int', 'auto_increment' => true];
        $this->_columns['service_id'] = ['type' => 'int'];
        $this->_columns['service_user'] = ['type' => 'int'];
        $this->_columns['badge_time'] = ['type' => 'int'];
        $this->_columns['badge_hash'] = ['type' => 'bigint'];
        $this->_columns['brief'] = ['type' => 'text'];
        $this->_columns['data'] = ['type' => 'jsonb'];

        $this->addIndex('badgehash', ['service_id', 'service_user', 'badge_time', 'badge_hash'], 'unique');
    }

    public static function getBadgeRank($badges)
    {
        $ids = $badges->toArray('id');
        $sql = "SELECT id, 
                        (SELECT COUNT(*) FROM service_badge WHERE main.service_id = service_id AND main.badge_hash = badge_hash) AS total,
                        (SELECT COUNT(*) FROM service_badge WHERE main.service_id = service_id AND main.badge_hash = badge_hash AND main.badge_time > badge_time) AS rank
                FROM service_badge AS main WHERE id IN (" . implode(',', $ids) . ")";
        $res = ServiceBadge::getDb()->query($sql);
        $ranks = new StdClass;
        while ($row = $res->fetch_assoc()) {
            $ranks->{$row['id']} = [$row['rank'] + 1, $row['total']];
        }
        return $ranks;
    }

    public static function getBadgeList($service_id, $hash)
    {
        $ret = [];
        $prev_time = null;
        $rank = 1;
        $sql = sprintf("SELECT badge_time, service_user.data->>'name' AS name, \"user\".name AS user_id
            FROM service_badge 
            JOIN service_user ON service_badge.service_user = service_user.id
            LEFT JOIN \"user\" ON \"user\".data->'service_users' @> TO_JSONB(service_user.id)
            WHERE service_user.service_id = %d AND badge_hash = %d ORDER BY badge_time ASC", intval($service_id), intval($hash));
        $res = ServiceBadge::getDb()->query($sql);
        while ($row = $res->fetch_assoc()) {
            $obj = new StdClass;
            $obj->badge_time = $row['badge_time'];
            $obj->name = $row['name'];
            $obj->user_id = $row['user_id'];
            if (is_null($prev_time) or $prev_time != $row['badge_time']) {
                $rank = count($ret) + 1;
                $prev_time = $row['badge_time'];
            }
            $obj->rank = $rank;
            $ret[] = $obj;
        }
        return $ret;
    }
}
