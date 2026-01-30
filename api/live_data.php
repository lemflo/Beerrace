<?php
include '../includes/db.php';
$act = $db->query("SELECT r.*, p.teamname, s.name as sname FROM results r JOIN participants p ON (r.participant_id=p.custom_id OR r.participant_id=p.id) JOIN stations s ON r.station_id=s.id WHERE r.status='active'");
echo "<h4>📍 Aktiv</h4><table>";
while($r=$act->fetchArray()){ echo "<tr><td>{$r['sname']}</td><td><strong>{$r['teamname']}</strong></td><td>".date("H:i",strtotime($r['checkin_at']))."</td></tr>"; }
echo "</table><hr>";
$done = $db->query("SELECT r.*, p.teamname, s.name as sname FROM results r JOIN participants p ON (r.participant_id=p.custom_id OR r.participant_id=p.id) JOIN stations s ON r.station_id=s.id WHERE r.status='done' ORDER BY r.checkout_at DESC LIMIT 10");
echo "<h4>🏆 Letzte Zieleinläufe</h4><table>";
while($r=$done->fetchArray()){ echo "<tr><td><strong>{$r['teamname']}</strong></td><td>{$r['sname']}</td><td>{$r['bottles']}🍺 | {$r['points']} Pkt / {$r['penalty_points']}⚠️</td></tr>"; }
echo "</table>";
?>
