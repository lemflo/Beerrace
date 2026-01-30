<?php
// Konfiguration: Wie viele Sekunden Strafe pro Strafpunkt?
$seconds_per_penalty = 60; 

// Alle Teams laden
$teams_res = $db->query("SELECT * FROM participants ORDER BY teamname ASC");

$final_scores = [];

while ($team = $teams_res->fetchArray(SQLITE3_ASSOC)) {
    $tid = $team['id'];
    $cid = $team['custom_id'];

    // 1. Start- und Zielzeit holen
    // Wir nehmen die kleinste Startzeit und die größte Zielzeit
    $start = $db->querySingle("SELECT MIN(time_seconds) FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND type='start_time' AND status='done'");
    $end = $db->querySingle("SELECT MAX(time_seconds) FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND type='end_time' AND status='done'");

    // 2. Summe der Parcours-Zeiten (Typ 'time')
    $parcours_time = $db->querySingle("SELECT SUM(time_seconds) FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND type='time' AND status='done'") ?: 0;

    // 3. Summe der Strafpunkte und Biere
    $stats = $db->query("SELECT SUM(penalty_points) as total_penalties, SUM(bottles) as total_bottles, SUM(points) as total_points 
                         FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND status='done'")->fetchArray(SQLITE3_ASSOC);

    $total_bottles = $stats['total_bottles'] ?: 0;
    $total_penalties = $stats['total_penalties'] ?: 0;
    $total_points = $stats['total_points'] ?: 0;

    // Berechnung der Netto-Zeit
    $net_race_time = 0;
    if ($start > 0 && $end > 0) {
        $net_race_time = $end - $start;
    }

    // Gesamtzeit = Netto-Rennen + Parcours-Spiele + (Strafpunkte * X Sekunden)
    $final_time_seconds = $net_race_time + $parcours_time + ($total_penalties * $seconds_per_penalty);

    $final_scores[] = [
        'teamname' => $team['teamname'],
        'members' => $team['p1'] . " & " . $team['p2'],
        'net_race' => $net_race_time,
        'parcours' => $parcours_time,
        'bottles' => $total_bottles,
        'penalties' => $total_penalties,
        'points' => $total_points,
        'final_time' => $final_time_seconds,
        'has_finished' => ($start > 0 && $end > 0)
    ];
}

// Sortierung: Wer die geringste Gesamtzeit hat, gewinnt
usort($final_scores, function($a, $b) {
    if (!$a['has_finished']) return 1;
    if (!$b['has_finished']) return -1;
    return $a['final_time'] <=> $b['final_time'];
});
?>

<h2>🏆 Gesamtauswertung / Rangliste</h2>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Rang</th>
                <th>Team</th>
                <th>Laufzeit (Netto)</th>
                <th>Spiele-Zeit</th>
                <th>Strafen</th>
                <th>Gesamtzeit</th>
                <th>🍺</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $rank = 1;
            foreach ($final_scores as $score): 
                $time_str = $score['has_finished'] ? gmdate("H:i:s", $score['final_time']) : "N/A (Kein Ziel)";
                $net_str = gmdate("H:i:s", $score['net_race']);
                $parc_str = gmdate("i:s", $score['parcours']);
            ?>
                <tr style="<?php echo !$score['has_finished'] ? 'opacity:0.5;' : ''; ?>">
                    <td><strong>#<?php echo $rank++; ?></strong></td>
                    <td>
                        <strong><?php echo htmlspecialchars($score['teamname']); ?></strong><br>
                        <small><?php echo htmlspecialchars($score['members']); ?></small>
                    </td>
                    <td><?php echo $net_str; ?></td>
                    <td>+ <?php echo $parc_str; ?></td>
                    <td><?php echo $score['penalties']; ?> ⚠️</td>
                    <td><strong style="color:#27ae60;"><?php echo $time_str; ?></strong></td>
                    <td><?php echo $score['bottles']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card" style="background: #eee;">
    <h4>Info zur Berechnung</h4>
    <ul>
        <li><strong>Laufzeit:</strong> Differenz zwischen Station "Startzeit" und "Zielzeit".</li>
        <li><strong>Spiele-Zeit:</strong> Summe aller Zeiten von Stationen des Typs "Parcours".</li>
        <li><strong>Gesamtzeit:</strong> Laufzeit + Spiele-Zeit + (Strafpunkte × <?php echo $seconds_per_penalty; ?> Sek).</li>
        <li>Die Biere werden aktuell nur gezählt, beeinflussen die Zeit aber nicht (kann im Code angepasst werden).</li>
    </ul>
</div>
