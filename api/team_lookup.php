<?php
include '../includes/db.php';

$id = $_GET['id'] ?? '';
if (empty($id)) exit(json_encode(['found' => false]));

// Team suchen (entweder über die vergebene custom_id oder die interne Datenbank-ID)
$stmt = $db->prepare("SELECT * FROM participants WHERE custom_id = :id OR id = :id_int");
$stmt->bindValue(':id', $id);
$stmt->bindValue(':id_int', (int)$id);
$team = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$team) {
    exit(json_encode(['found' => false]));
}

// Bisherige Ergebnisse für die Anzeige der Historie laden
$res_sql = $db->prepare("SELECT r.bottles, s.name as sname FROM results r 
                          JOIN stations s ON r.station_id = s.id 
                          WHERE (r.participant_id = :cid OR r.participant_id = :tid) 
                          AND r.status = 'done'");
$res_sql->bindValue(':cid', $team['custom_id']);
$res_sql->bindValue(':tid', (string)$team['id']);
$res = $res_sql->execute();

$history = [];
$total_bottles = 0;
while($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $total_bottles += $row['bottles'];
    $history[] = ['sname' => $row['sname'], 'bottles' => $row['bottles']];
}

echo json_encode([
    'found' => true,
    'teamname' => $team['teamname'],
    'total_bottles' => $total_bottles,
    'history' => $history
]);
