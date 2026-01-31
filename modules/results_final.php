<?php
$seconds_per_penalty = 60; 

function getFinalScores($db, $seconds_per_penalty) {
    $teams_res = $db->query("SELECT * FROM participants ORDER BY teamname ASC");
    $scores = [];

    while ($team = $teams_res->fetchArray(SQLITE3_ASSOC)) {
        $tid = (string)$team['id'];
        $cid = (string)$team['custom_id'];
        $id_query = "(participant_id='$tid' OR participant_id='$cid')";

        // Start und Ziel finden
        $start = $db->querySingle("SELECT time_seconds FROM results WHERE $id_query AND type='start_time' AND status='done' ORDER BY timestamp DESC LIMIT 1");
        $end = $db->querySingle("SELECT time_seconds FROM results WHERE $id_query AND type='end_time' AND status='done' ORDER BY timestamp DESC LIMIT 1");

        $parcours_time = $db->querySingle("SELECT SUM(time_seconds) FROM results WHERE $id_query AND type='time' AND status='done'") ?: 0;
        $stats = $db->query("SELECT SUM(penalty_points) as tp, SUM(bottles) as tb FROM results WHERE $id_query AND status='done'")->fetchArray(SQLITE3_ASSOC);
        
        $total_penalties = $stats['tp'] ?: 0;
        $total_bottles = $stats['tb'] ?: 0;
        $manual_adj = (int)($team['time_adjustment'] ?? 0);

        $has_finished = ($start > 0 && $end > 0);
        $net_race_time = $has_finished ? ($end - $start) : 0;
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

$scores = getFinalScores($db, $seconds_per_penalty);
?>

<div style="position: relative; margin-bottom: 20px;">
    <h2>🏆 Rangliste</h2>
    <a href="index.php?module=results_final&export=csv" class="btn-success" 
       style="position: absolute; top: 0; right: 0; text-decoration: none; padding: 6px 12px; font-size: 0.75rem; width: auto; display: inline-block;">
       📥 CSV Export
    </a>
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
            <tr style="<?= $s['has_finished'] ? '' : 'opacity:0.5;' ?>">
                <td>#<?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($s['teamname']) ?></strong></td>
                <td><strong><?= $s['has_finished'] ? gmdate("H:i:s", $s['final_time']) : '--:--:--' ?></strong></td>
                <td><?= $s['bottles'] ?></td>
                <td>
                    <?php if($s['adj'] != 0): ?>
                        <small style="background:#eee; padding:2px 4px;"><?= ($s['adj']>0?'+':'').$s['adj'] ?>s</small>
                    <?php endif; ?>
                    <?= !$s['has_finished'] ? '<small style="color:red;">Unvollständig</small>' : '' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
