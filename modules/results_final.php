<?php
// Konfiguration: Wie viele Sekunden Strafe pro Strafpunkt?
$seconds_per_penalty = 60; 

// Hilfsfunktion zur Berechnung der Scores (wird für Anzeige und Export genutzt)
function getFinalScores($db, $seconds_per_penalty) {
    $teams_res = $db->query("SELECT * FROM participants ORDER BY teamname ASC");
    $scores = [];

    while ($team = $teams_res->fetchArray(SQLITE3_ASSOC)) {
        $tid = $team['id'];
        $cid = $team['custom_id'];

        $start = $db->querySingle("SELECT MIN(time_seconds) FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND type='start_time' AND status='done'");
        $end = $db->querySingle("SELECT MAX(time_seconds) FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND type='end_time' AND status='done'");
        $parcours_time = $db->querySingle("SELECT SUM(time_seconds) FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND type='time' AND status='done'") ?: 0;
        
        $stats = $db->query("SELECT SUM(penalty_points) as tp, SUM(bottles) as tb, SUM(points) as tpts FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND status='done'")->fetchArray(SQLITE3_ASSOC);

        $net_race_time = ($start > 0 && $end > 0) ? ($end - $start) : 0;
        $final_time = $net_race_time + $parcours_time + (($stats['tp'] ?: 0) * $seconds_per_penalty);

        $scores[] = [
            'teamname' => $team['teamname'],
            'members' => $team['p1'] . " & " . $team['p2'],
            'net_race' => $net_race_time,
            'parcours' => $parcours_time,
            'bottles' => $stats['tb'] ?: 0,
            'penalties' => $stats['tp'] ?: 0,
            'points' => $stats['tpts'] ?: 0,
            'final_time' => $final_time,
            'has_finished' => ($start > 0 && $end > 0)
        ];
    }

    usort($scores, function($a, $b) {
        if (!$a['has_finished']) return 1;
        if (!$b['has_finished']) return -1;
        return $a['final_time'] <=> $b['final_time'];
    });
    return $scores;
}

// EXPORT LOGIK
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $data = getFinalScores($db, $seconds_per_penalty);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=beerrace_results_'.date('Y-m-d').'.csv');
    $output = fopen('php://output', 'w');
    // UTF-8 BOM für Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['Rang', 'Team', 'Mitglieder', 'Netto-Laufzeit (sek)', 'Parcours (sek)', 'Strafen', 'Punkte', 'Bier', 'Gesamtzeit (sek)'], ';');
    
    $r = 1;
    foreach ($data as $s) {
        fputcsv($output, [
            $r++, $s['teamname'], $s['members'], $s['net_race'], $s['parcours'], $s['penalties'], $s['points'], $s['bottles'], $s['final_time']
        ], ';');
    }
    fclose($output);
    exit;
}

$final_scores = getFinalScores($db, $seconds_per_penalty);
?>

<div style="display:flex; justify-content:space-between; align-items:center;">
    <h2>🏆 Gesamtauswertung</h2>
    <a href="index.php?module=results_final&export=csv" class="btn-primary" style="background:#2c3e50; text-decoration:none;">📥 CSV Export</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Rang</th>
                <th>Team</th>
                <th>Laufzeit</th>
                <th>Spiele</th>
                <th>Strafen</th>
                <th>Gesamt</th>
                <th>🍺</th>
            </tr>
        </thead>
        <tbody>
            <?php $rank = 1; foreach ($final_scores as $score): ?>
                <tr style="<?= !$score['has_finished'] ? 'opacity:0.5;' : '' ?>">
                    <td><strong>#<?= $rank++ ?></strong></td>
                    <td><strong><?= htmlspecialchars($score['teamname']) ?></strong><br><small><?= htmlspecialchars($score['members']) ?></small></td>
                    <td><?= gmdate("H:i:s", $score['net_race']) ?></td>
                    <td>+ <?= gmdate("i:s", $score['parcours']) ?></td>
                    <td><?= $score['penalties'] ?> ⚠️</td>
                    <td><strong><?= $score['has_finished'] ? gmdate("H:i:s", $score['final_time']) : "N/A" ?></strong></td>
                    <td><?= $score['bottles'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
