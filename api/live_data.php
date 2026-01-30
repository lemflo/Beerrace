<?php
include '../includes/db.php';

$sql = "SELECT r.*, p.teamname, s.name as sname 
        FROM results r 
        JOIN participants p ON (r.participant_id = p.custom_id OR r.participant_id = CAST(p.id AS TEXT))
        JOIN stations s ON r.station_id = s.id 
        ORDER BY r.timestamp DESC LIMIT 30";

$res = $db->query($sql);

echo "<table>";
echo "<thead><tr><th>Zeit</th><th>Team</th><th>Station</th><th>Ergebnis</th><th>Bier</th></tr></thead><tbody>";

while($r = $res->fetchArray(SQLITE3_ASSOC)) {
    $time = date("H:i", strtotime($r['timestamp']));
    $val = $r['type'] == 'time' ? gmdate("i:s", $r['time_seconds']) : $r['points'] . " Pkt";
    
    echo "<tr>
            <td>$time</td>
            <td><strong>" . htmlspecialchars($r['teamname']) . "</strong></td>
            <td>" . htmlspecialchars($r['sname']) . "</td>
            <td>$val</td>
            <td>" . str_repeat('🍺', min($r['bottles'], 5)) . " ({$r['bottles']})</td>
          </tr>";
}
echo "</tbody></table>";
?>
