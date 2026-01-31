<?php
$seconds_per_penalty = 60;

function getFinalScores($db, $seconds_per_penalty) {
    $teams_res = $db->query("SELECT * FROM participants");
    $scores = [];
    while ($team = $teams_res->fetchArray(SQLITE3_ASSOC)) {
        $tid = $team['id']; $cid = $team['custom_id'];
        $start = $db->querySingle("SELECT MIN(time_seconds) FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND type='start_time' AND status='done'");
        $end = $db->querySingle("SELECT MAX(time_seconds) FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND type='end_time' AND status='done'");
        $parcours = $db->querySingle("SELECT SUM(time_seconds) FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND type='time' AND status='done'") ?: 0;
        $stats = $db->query("SELECT SUM(penalty_points) as tp, SUM(bottles) as tb FROM results WHERE (participant_id='$tid' OR participant_id='$cid') AND status='done'")->fetchArray(SQLITE3_ASSOC);
        
        $net = ($start > 0 && $end > 0) ? ($end - $start) : 0;
        $adj = (int)$team['time_adjustment'];
        $final = $net + $parcours + ($stats['tp'] * $seconds_per_penalty) + $adj;
        
        $scores[] = [
            'teamname' => $team['teamname'],
            'members' => implode(", ", array_filter([$team['p1'],$team['p2'],$team['p3'],$team['p4']])),
            'final_time' => $final, 'bottles' => $stats['tb'] ?: 0, 'has_finished' => ($start > 0 && $end > 0), 'adj' => $adj
        ];
    }
    usort($scores, function($a, $b) { 
        if(!$a['has_finished']) return 1; if(!$b['has_finished']) return -1;
        return $a['final_time'] <=> $b['final_time']; 
    });
    return $scores;
}

if (isset($_GET['export'])) {
    $data = getFinalScores($db, $seconds_per_penalty);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=results.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['Rang','Team','Mitglieder','Zeit (Sek)','Bier','Korrektur'], ';');
    foreach($data as $i=>$s) fputcsv($output, [$i+1, $s['teamname'], $s['members'], $s['final_time'], $s['bottles'], $s['adj']], ';');
    exit;
}

$scores = getFinalScores($db, $seconds_per_penalty);
?>
<div style="display:flex; justify-content:space-between; align-items:center;">
    <h2>🏆 Rangliste</h2>
    <a href="index.php?module=results_final&export=csv" class="btn-primary">📥 CSV</a>
</div>
<div class="card">
    <table>
        <thead><tr><th>Rang</th><th>Team</th><th>Gesamtzeit</th><th>Info</th><th>🍺</th></tr></thead>
        <tbody>
            <?php foreach($scores as $i=>$s): ?>
            <tr style="<?=$s['has_finished']?'':'opacity:0.5'?>">
                <td>#<?=$i+1?></td>
                <td><strong><?=htmlspecialchars($s['teamname'])?></strong><br><small><?=$s['members']?></small></td>
                <td><?=$s['has_finished']?gmdate("H:i:s",$s['final_time']):'--'?></td>
                <td><small><?=$s['adj']!=0 ? ($s['adj']>0?"+$s[adj]s Strafe":"$s[adj]s Bonus"):''?></small></td>
                <td><?=$s['bottles']?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
