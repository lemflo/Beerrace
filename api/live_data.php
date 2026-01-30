<?php
include '../includes/db.php';

// 1. Aktive Teams an Stationen (Wer ist gerade wo?)
$active_res = $db->query("SELECT r.checkin_at, p.teamname, s.name as sname FROM results r 
                          JOIN participants p ON (r.participant_id = p.custom_id OR r.participant_id = CAST(p.id AS TEXT))
                          JOIN stations s ON r.station_id = s.id 
                          WHERE r.status = 'active'
                          ORDER BY r.checkin_at DESC");

echo "<h3>📍 Aktuell an den Stationen</h3>";
echo "<table><thead><tr><th>Station</th><th>Team</th><th>Eingecheckt</th></tr></thead><tbody>";
$has_active = false;
while($r = $active_res->fetchArray(SQLITE3_ASSOC)) {
    $has_active = true;
    echo "<tr>
            <td>" . htmlspecialchars($r['sname']) . "</td>
            <td><strong>" . htmlspecialchars($r['teamname']) . "</strong></td>
            <td>" . date("H:i", strtotime($r['checkin_at'])) . " Uhr</td>
          </tr>";
}
if(!$has_active) echo "<tr><td colspan='3'>Gerade kein Team in Bearbeitung.</td></tr>";
echo "</tbody></table><br><hr><br>";

// 2. Letzte Ergebnisse (Wer hat gerade eine Station abgeschlossen?)
$done_res = $db->query("SELECT r.*, p.teamname, s.name as sname FROM results r 
                        JOIN participants p ON (r.participant_id = p.custom_id OR r.participant_id = CAST(p.id AS TEXT))
                        JOIN stations s ON r.station_id = s.id 
                        WHERE r.status = 'done' 
                        ORDER BY r.checkout_at DESC LIMIT 10");

echo "<h3>🏆 Letzte Stations-Abschlüsse</h3>";
echo "<table><thead><tr><th>Team</th><th>Station</th><th>Ergebnis</th><th>Bier</th></tr></thead><tbody>";
while($r = $done_res->fetchArray(SQLITE3_ASSOC)) {
    // Formatierung des Wertes je nach Typ
    $val = $r['points'] . " Pkt";
    if($r['type'] == 'time') $val = gmdate("i:s", $r['time_seconds']) . " min";
    if(in_array($r['type'], ['start_time', 'end_time'])) $val = date("H:i", $r['time_seconds']) . " Uhr";

    echo "<tr>
            <td><strong>" . htmlspecialchars($r['teamname']) . "</strong></td>
            <td>" . htmlspecialchars($r['sname']) . "</td>
            <td>$val " . ($r['penalty_points'] > 0 ? " <span style='color:red;'>({$r['penalty_points']}⚠️)</span>" : "") . "</td>
            <td>{$r['bottles']} 🍺</td>
          </tr>";
}
echo "</tbody></table>";
