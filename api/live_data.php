<?php
include '../includes/db.php';
$res = $db->query("SELECT r.*, p.teamname, s.name as sname FROM results r JOIN participants p ON r.participant_id = p.custom_id OR r.participant_id = p.id JOIN stations s ON r.station_id = s.id ORDER BY r.timestamp DESC LIMIT 20");
echo "<table><tr><th>Zeit</th><th>Team</th><th>Station</th><th>Wert</th></tr>";
while($r = $res->fetchArray()) {
    $val = $r['type']=='time' ? gmdate("i:s", $r['time_seconds']) : $r['points'];
    echo "<tr><td>".date("H:i", strtotime($r['timestamp']))."</td><td>{$r['teamname']}</td><td>{$r['sname']}</td><td>$val</td></tr>";
}
echo "</table>";
?>
