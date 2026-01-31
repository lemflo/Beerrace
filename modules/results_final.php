<?php
// Konfiguration: Wie viele Sekunden Strafe pro Strafpunkt?
$seconds_per_penalty = 60; 

/**
 * Berechnet die Ergebnisse für alle Teams
 */
function getFinalScores($db, $seconds_per_penalty) {
    $teams_res = $db->query("SELECT * FROM participants ORDER BY teamname ASC");
    $scores = [];

    while ($team = $teams_res->fetchArray(SQLITE3_ASSOC)) {
        $tid = $team['id'];
        $cid = $team['custom_id'];

        // 1. Start- und Zielzeit ermitteln (Typ: start_time / end_time)
        // Wir suchen nach beiden IDs (interne ID und QR-ID), um Fehler beim Scannen abzufangen
        $start = $db->querySingle("SELECT MIN(time_seconds) FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND type='start_time' AND status='done'");
        $end = $db->querySingle("SELECT MAX(time_seconds) FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND type='end_time' AND status='done'");

        // 2. Reine Parcours-Zeiten summieren (Typ: time)
        $parcours_time = $db->querySingle("SELECT SUM(time_seconds) FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND type='time' AND status='done'") ?: 0;

        // 3. Strafpunkte und Biere summieren
        $stats = $db->query("SELECT SUM(penalty_points) as tp, SUM(bottles) as tb FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND status='done'")->fetchArray(SQLITE3_ASSOC);
        
        $total_penalties = $stats['tp'] ?: 0;
        $total_bottles = $stats['tb'] ?: 0;

        // 4. Netto-Rennzeit berechnen (Ende - Start)
        $net_race_time = 0;
        $has_finished = false;
        if ($start > 0 && $end > 0) {
            $net_race_time = $end - $start;
            $has_finished = true;
        }

        // 5. Manuelle Korrektur aus den Stammdaten laden
        $manual_adj = (int)($team['time_adjustment'] ?? 0);

        // 6. GESAMTZEIT BERECHNEN:
        // Netto-Zeit + Parcours-Zeiten + (Strafpunkte * X Sek) + Manuelle Korrektur
        $final_time_seconds = $net_race_time + $parcours_time + ($total_penalties * $seconds_per_penalty) + $manual_adj;

        $scores[] = [
            'teamname' => $team['teamname'],
            'members' => implode(", ", array_filter([$team['p1'], $team['p2'], $team['p3'], $team['p4']])),
            'final_time' => $final_time_seconds,
            'bottles' => $total_bottles,
            'has_finished' => $has_finished,
            'adj' => $manual_adj,
            'penalties' => $total_penalties
        ];
    }

    // Sortierung: Wer fertig ist kommt nach oben, dann nach Zeit (aufsteigend)
    usort($scores, function($a, $b) {
        if ($a['has_finished'] && !$b['has_finished']) return -1;
        if (!$a['has_finished'] && $b['has_finished']) return 1;
        return $a['final_time'] <=> $b['final_time'];
    });

    return $scores;
}

// CSV Export Logik
if (isset($_GET['export'])) {
    $data = getFinalScores($db, $seconds_per_penalty);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=beerrace_ergebnisse.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM für Excel
    fputcsv($output, ['Rang', 'Team', 'Mitglieder', 'Gesamtzeit', 'Biere', 'Zeitstrafe/Bonus (Sek)'], ';');
    
    foreach ($data as $i => $s) {
        fputcsv($output, [
            $i + 1,
            $s['teamname'],
            $s['members'],
            $s['has_finished'] ? gmdate("H:i:s", $s['final_time']) : 'Nicht im Ziel',
            $s['bottles'],
            $s['adj']
        ], ';');
    }
    exit;
}

$scores = getFinalScores($db, $seconds_per_penalty);
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <h2>🏆 Gesamtrangliste</h2>
    <a href="index.php?module=results_final&export=csv" class="btn-success" style="text-decoration:none;">📥 CSV Export</a>
</div>

<div class="card">
    <table style="width:100%;">
        <thead>
            <tr>
                <th>Rang</th>
                <th>Team / Mitglieder</th>
                <th>Gesamtzeit</th>
                <th>🍺</th>
                <th>Info</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($scores as $i => $s): ?>
            <tr style="<?= $s['has_finished'] ? '' : 'background:#fff5f5; color:#999;' ?>">
                <td><strong style="font-size:1.2em;">#<?= $i + 1 ?></strong></td>
                <td>
                    <strong><?= htmlspecialchars($s['teamname']) ?></strong><br>
                    <small><?= htmlspecialchars($s['members']) ?></small>
                </td>
                <td>
                    <span style="font-family:monospace; font-weight:bold;">
                        <?= $s['has_finished'] ? gmdate("H:i:s", $s['final_time']) : '--:--:--' ?>
                    </span>
                </td>
                <td><?= $s['bottles'] ?></td>
                <td>
                    <?php if($s['adj'] != 0): ?>
                        <small title="Manuelle Korrektur" style="background:#fff3cd; padding:2px 4px; border-radius:3px;">
                            <?= $s['adj'] > 0 ? "+".$s['adj'] : $s['adj'] ?>s
                        </small>
                    <?php endif; ?>
                    <?php if(!$s['has_finished']): ?>
                        <small style="color:red;">Start/Ziel fehlt</small>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
