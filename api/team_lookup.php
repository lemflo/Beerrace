<?php
include '../includes/db.php';
$id = $_GET['id'] ?? '';
$t = $db->query("SELECT * FROM participants WHERE custom_id='$id' OR id='$id'")->fetchArray(SQLITE3_ASSOC);
if(!$t) exit(json_encode(['found'=>false]));
$res = $db->query("SELECT r.*, s.name as sname FROM results r JOIN stations s ON r.station_id=s.id WHERE r.participant_id='{$t['custom_id']}' OR r.participant_id='{$t['id']}'");
$hist = []; $tot = 0;
while($row=$res->fetchArray(SQLITE3_ASSOC)){ $tot+=$row['bottles']; $hist[]=['sname'=>$row['sname'],'bottles'=>$row['bottles']]; }
echo json_encode(['found'=>true,'teamname'=>$t['teamname'],'total_bottles'=>$tot,'history'=>$hist]);
?>
