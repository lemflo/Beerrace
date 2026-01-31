<?php
// Konfiguration: Wie viele Sekunden Strafe pro Strafpunkt?
$seconds_per_penalty = 60; 

function getFinalScores($db, $seconds_per_penalty) {
    $teams_res = $db->query("SELECT * FROM participants ORDER BY teamname ASC");
    $scores = [];

    while ($team = $teams_res->fetchArray(SQLITE3_ASSOC)) {
        $tid = (string)$team['id'];
        $cid = (string)$team['custom_id'];

        // 1. Start- und Zielzeit ermitteln
        // Wir suchen explizit nach beiden möglichen IDs im Feld participant_id
        $start = $db->querySingle("SELECT MIN(time_seconds) FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND type='start_time' AND status='done'");
        $end = $db->querySingle("SELECT MAX(time_seconds) FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND type='end_time' AND status='done'");

        // 2. Reine Parcours-Zeiten summieren (Stationen vom Typ 'time')
        $parcours_time = $db->querySingle("SELECT SUM(time_seconds) FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND type='time' AND status='done'") ?: 0;

        // 3. Strafpunkte und Biere summieren
        $stats = $db->query("SELECT SUM(penalty_points) as tp, SUM(bottles) as tb FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND status='done'")->fetchArray(SQLITE3_ASSOC);
        
        $total_penalties = $stats['tp'] ?: 0;
        $total_bottles = $stats['tb'] ?: 0;

        // 4. Netto-Rennzeit berechnen
        $net_race_time = 0;
        $has_finished = false;
        
        // WICHTIG: Prüfung ob beide Zeiten existieren und ungleich 0 sind
        if ($start > 0 && $end > 0) {
            $net_race_time = $end - $start;
            $has_finished = true;
        }

        // 5. Manuelle Korrektur
        $manual_adj = (int)($team['time_adjustment'] ?? 0);

        // 6. Gesamtzeit
        $final_time_seconds = $net_race_time + $parcours_time + ($total_penalties * $seconds_per_penalty) + $manual_adj;

        $scores[] = [
            'teamname' => $team['teamname'],
            'members' => implode(", ", array_filter([$team['p1'], $team['p2'], $team['p3'], $team['p4']])),
            'final_time' => $final_time_seconds,
            'bottles' => $total_bottles,
            'has_finished' => $has_finished,
            'adj' => $manual_adj
        ];
    }

    usort($scores, function($a, $b) {
        if ($a['has_finished'] && !$b['has_finished']) return -1;
        if (!$a['has_finished'] && $b['has_finished']) return 1;
        return $a['final_time'] <=> $b['final_time'];
    });

    return $scores;
}

// CSV Export
if (isset($_GET['export'])) {
    $data = getFinalScores($db, $seconds_per_penalty);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ergebnisse.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['Rang', 'Team', 'Zeit', 'Bier'], ';');
    foreach ($data as $i => $s) {
        fputcsv($output, [$i+1, $s['teamname'], $s['has_finished'] ? gmdate("H:i:s", $s['final_time']) : 'n.a.', $s['bottles']], ';');
    }
    exit;
}

$scores = getFinalScores($db, $seconds_per_penalty);
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <h2>🏆 Rangliste</h2>
    <a href="index.php?module=results_final&export=csv" class="btn-success" style="text-decoration:none; padding: 5px 10px; font-size: 0.75rem; height: fit-content;">📥 CSV Export</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Rang</th>
                <th>Team</th>
                <th>Gesamtzeit</th>
                <th>🍺</th>
                <th>Info</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($scores as $i => $s): ?>
            <tr style="<?= $s['has_finished'] ? '' : 'opacity:0.6;' ?>">
                <td>#<?= $i + 1 ?></td>
                <td>
                    <strong><?= htmlspecialchars($s['teamname']) ?></strong><br>
                    <small style="color:#666;"><?= htmlspecialchars($s['members']) ?></small>
                </td>
                <td>
                    <strong><?= $s['has_finished'] ? gmdate("H:i:s", $s['final_time']) : '--:--:--' ?></strong>
                </td>
                <td><?= $s['bottles'] ?></td>
                <td>
                    <?php if($s['adj'] != 0): ?>
                        <small style="background:#eee; padding:2px 5px; border-radius:3px;"><?= ($s['adj']>0?'+':'').$s['adj'] ?>s</small>
                    <?php endif; ?>
                    <?php if(!$s['has_finished']): ?>
                        <small style="color:#e74c3c; font-weight:bold;">Unvollständig</small>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
