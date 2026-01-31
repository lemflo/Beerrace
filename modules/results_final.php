<?php
// Konfiguration: Strafsekunden pro Punkt
$seconds_per_penalty = 60; 

/**
 * Kern-Funktion zur Berechnung der Rangliste
 */
function getFinalScores($db, $seconds_per_penalty) {
    $teams_res = $db->query("SELECT * FROM participants ORDER BY teamname ASC");
    $scores = [];

    while ($team = $teams_res->fetchArray(SQLITE3_ASSOC)) {
        $tid = (string)$team['id'];
        $cid = (string)$team['custom_id'];
        
        // Wir suchen Ergebnisse, die ENTWEDER auf die interne ID ODER die Startnummer gebucht wurden
        $id_filter = "(participant_id = '$tid' OR participant_id = '$cid')";

        // Start & Ziel Zeiten (Typ: start_time / end_time)
        $startTime = $db->querySingle("SELECT time_seconds FROM results WHERE $id_filter AND type='start_time' AND status='done' ORDER BY timestamp DESC LIMIT 1") ?: 0;
        $endTime = $db->querySingle("SELECT time_seconds FROM results WHERE $id_filter AND type='end_time' AND status='done' ORDER BY timestamp DESC LIMIT 1") ?: 0;

        // Zusätzliche Parcours-Zeiten (Typ: time)
        $parcoursSeconds = $db->querySingle("SELECT SUM(time_seconds) FROM results WHERE $id_filter AND type='time' AND status='done'") ?: 0;

        // Biere und Strafpunkte
        $stats = $db->query("SELECT SUM(penalty_points) as tp, SUM(bottles) as tb FROM results WHERE $id_filter AND status='done'")->fetchArray(SQLITE3_ASSOC);
        $totalPenalties = $stats['tp'] ?: 0;
        $totalBottles = $stats['tb'] ?: 0;
        
        // Manuelle Zeitkorrektur (Bonus/Malus)
        $manualAdj = (int)($team['time_adjustment'] ?? 0);

        // Validierung: Hat das Team das Rennen beendet?
        $hasFinished = ($startTime > 0 && $endTime > 0);
        $nettoRaceTime = $hasFinished ? ($endTime - $startTime) : 0;

        // GESAMTZEIT BERECHNUNG
        $finalSeconds = $nettoRaceTime + $parcoursSeconds + ($totalPenalties * $seconds_per_penalty) + $manualAdj;

        $scores[] = [
            'id' => $tid,
            'teamname' => $team['teamname'] ?: 'Unbekanntes Team',
            'members' => implode(", ", array_filter([$team['p1'], $team['p2'], $team['p3'], $team['p4']])),
            'final_time' => $finalSeconds,
            'bottles' => $totalBottles,
            'has_finished' => $hasFinished,
            'adj' => $manualAdj
        ];
    }

    // Sortierung: Wer fertig ist zuerst, dann nach Zeit (aufsteigend)
    usort($scores, function($a, $b) {
        if ($a['has_finished'] && !$b['has_finished']) return -1;
        if (!$a['has_finished'] && $b['has_finished']) return 1;
        return $a['final_time'] <=> $b['final_time'];
    });

    return $scores;
}

// 1. CSV EXPORT LOGIK (Muss oben stehen)
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $data = getFinalScores($db, $seconds_per_penalty);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=beerrace_results.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 Fix
    fputcsv($output, ['Rang', 'Team', 'Zeit', 'Biere'], ';');
    foreach ($data as $i => $s) {
        fputcsv($output, [$i+1, $s['teamname'], $s['has_finished'] ? gmdate("H:i:s", $s['final_time']) : 'n.a.', $s['bottles']], ';');
    }
    fclose($output);
    exit;
}

// 2. DATEN FÜR DIE ANSICHT LADEN
$scores = getFinalScores($db, $seconds_per_penalty);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2 style="margin: 0;">🏆 Gesamtrangliste</h2>
    <a href="index.php?module=results_final&export=csv" class="btn-success" 
       style="text-decoration: none; padding: 6px 12px; font-size: 0.75rem; width: auto; white-space: nowrap;">
       📥 CSV Export
    </a>
</div>

<div class="card" style="padding: 0; overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse;">
        <thead style="background: #f8f9fa;">
            <tr>
                <th style="padding: 12px;">Rang</th>
                <th style="padding: 12px;">Team / Mitglieder</th>
                <th style="padding: 12px;">Gesamtzeit</th>
                <th style="padding: 12px;">🍺</th>
                <th style="padding: 12px;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($scores)): ?>
                <tr><td colspan="5" style="padding: 20px; text-align: center;">Keine Teams registriert.</td></tr>
            <?php else: ?>
                <?php foreach ($scores as $i => $s): ?>
                <tr style="border-bottom: 1px solid #eee; <?= !$s['has_finished'] ? 'background: #fffafa;' : '' ?>">
                    <td style="padding: 12px; text-align: center;"><strong>#<?= $i + 1 ?></strong></td>
                    <td style="padding: 12px;">
                        <a href="index.php?module=team_edit&id=<?= $s['id'] ?>" style="text-decoration: none; color: var(--primary); font-weight: bold;">
                            <?= htmlspecialchars($s['teamname']) ?>
                        </a><br>
                        <small style="color: #666;"><?= htmlspecialchars($s['members']) ?></small>
                    </td>
                    <td style="padding: 12px; font-family: monospace; font-weight: bold;">
                        <?= $s['has_finished'] ? gmdate("H:i:s", $s['final_time']) : '--:--:--' ?>
                    </td>
                    <td style="padding: 12px; text-align: center;"><?= $s['bottles'] ?></td>
                    <td style="padding: 12px;">
                        <?php if($s['adj'] != 0): ?>
                            <span style="font-size: 0.7rem; background: #eee; padding: 2px 5px; border-radius: 3px; margin-right: 5px;">
                                <?= ($s['adj'] > 0 ? '+' : '') . $s['adj'] ?>s
                            </span>
                        <?php endif; ?>
                        <?= $s['has_finished'] ? '✅' : '<small style="color: #e74c3c;">Unvollständig</small>' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
