<?php

include(__DIR__ . '/../init.inc.php');
$service_id = $_SERVER['argv'][1];
if (!$s = Service::find_by_service_id(strval($service_id))) {
    throw new Exception("找不到 {$service_id}");
}

$id_file = __DIR__ . "/{$service_id}-id.csv";
$badge_file = __DIR__ . "/{$service_id}-badge.jsonl";

if (!file_exists($id_file) or !file_exists($badge_file)) {
    throw new Exception("找不到匯入檔");
}

$fp = fopen($id_file, 'r');
$columns = fgetcsv($fp);

$db = ServiceUser::getDb();
$db->query("CREATE TEMP TABLE tmp_user (LIKE service_user)");
$terms = [];
$id = 0;
while ($row = fgetcsv($fp)) {
    $values = array_combine($columns, $row);
    $terms[] = sprintf("(%d,%d,%s,%s)",
        $id ++,
        intval($s->id),
        $db->quoteWithColumn('data', $values['uid']),
        $db->quoteWithColumn('data', json_encode([
            'name' => $values['name'],
        ]))
    );
}

$db->query("INSERT INTO tmp_user (id,service_id, user_id, data) VALUES " . implode(',', $terms));

$sql = "UPDATE service_user SET data = tmp_user.data FROM tmp_user WHERE service_user.service_id = tmp_user.service_id AND service_user.user_id = tmp_user.user_id AND (service_user.data)::text != (tmp_user.data)::text RETURNING service_user.id";
$res = $db->query($sql);
$update = 0;
while ($row = $res->fetch_assoc()) {
    $update ++;
}

$sql = "INSERT INTO service_user (service_id,user_id,data) SELECT service_id,user_id,data FROM tmp_user WHERE (service_id, user_id) IN (SELECT service_id, user_id FROM tmp_user EXCEPT SELECT service_id, user_id FROM service_user) RETURNING service_user.id;";
$res = $db->query($sql);
$insert = 0;
while ($row = $res->fetch_assoc()) {
    $insert ++;
}
echo "update={$update}, insert={$insert}\n";
$db->query("DROP TABLE tmp_user");

$user_map = [];
foreach (ServiceUser::search(['service_id' => $s->id])->toArray(['user_id', 'id']) as $d) {
    $user_map[$d['user_id']] = $d['id'];
}

$fp = fopen($badge_file, 'r');

$db->query("CREATE TEMP TABLE tmp_badge (LIKE service_badge)");
$terms = [];
$id = 0;
while ($row = fgets($fp)) {
    $row = json_decode($row);
    $terms[] = sprintf("(%d,%d,%d,%d,%d,%s,%s)",
        $id ++,
        intval($s->id),
        $user_map[$row->uid],
        strtotime($row->time),
        crc32($row->brief),
        $db->quoteWithColumn('data', $row->brief),
        $db->quoteWithColumn('data', json_encode(
            $row->extra
        ))
    );
}

$db->query("INSERT INTO tmp_badge (id,service_id,service_user,badge_time,badge_hash,brief,data) VALUES " . implode(',', $terms));

$sql = "UPDATE service_badge SET data = tmp_badge.data FROM tmp_badge WHERE service_badge.service_id = tmp_badge.service_id AND service_badge.service_user = tmp_badge.service_user AND service_badge.badge_time = tmp_badge.badge_time AND service_badge.badge_hash = tmp_badge.badge_hash AND (service_badge.data)::text != (tmp_badge.data)::text RETURNING service_badge.id";
$res = $db->query($sql);
$update = 0;
while ($row = $res->fetch_assoc()) {
    $update ++;
}

$sql = "INSERT INTO service_badge (service_id,service_user,badge_time,badge_hash,brief,data) SELECT service_id,service_user,badge_time,badge_hash,brief,data FROM tmp_badge WHERE (service_id,service_user,badge_time,badge_hash) IN (SELECT service_id,service_user,badge_time,badge_hash FROM tmp_badge EXCEPT SELECT service_id,service_user,badge_time,badge_hash  FROM service_badge) RETURNING service_badge.id;";
$res = $db->query($sql);
$insert = 0;
while ($row = $res->fetch_assoc()) {
    $insert ++;
}
echo "update={$update}, insert={$insert}\n";
