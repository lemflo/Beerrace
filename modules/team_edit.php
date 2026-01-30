<?php
$tid = (int)$_GET['id'];
if (isset($_POST['update_team'])) {
    $stmt = $db->prepare("UPDATE participants SET teamname=:n, p1=:p1, p2=:p2, custom_id=:cid WHERE id=:id");
    $stmt->bindValue(':n',$_POST['tname']);$stmt->bindValue(':p1',$_POST['p1']);$stmt->bindValue(':p2',$_POST['p2']);$stmt->bindValue(':cid',$_POST['cid']);$stmt->bindValue(':id',$tid);$stmt->execute();
}
if (isset($_POST['update_res'])) {
    $stmt = $db->prepare("UPDATE results SET bottles=:b, points=:p, penalty_points=:pp, comment=:c WHERE id=:rid");
    $stmt->bindValue(':b',$_POST['b']);$stmt->bindValue(':p',$_POST['p']);$stmt->bindValue(':pp',$_POST['pp']);$stmt->bindValue(':c',$_POST['c']);$stmt->bindValue(':rid',$_POST['rid']);$stmt->execute();
}
$t = $db->query("SELECT * FROM participants WHERE id=$tid")->fetchArray(SQLITE3_ASSOC);
?>
<h2>Team-Akte: <?=$t['teamname']?></h2>
<form method="post" class="card"><h3>Stammdaten</h3><input type="text" name="tname" value="<?=$t['teamname']?>"><input type="text" name="p1" value="<?=$t['p1']?>"><input type="text" name="p2" value="<?=$t['p2']?>"><input type="text" name="cid" value="<?=$t['custom_id']?>"><button name="update_team" class="btn-primary">Speichern</button></form>
<h3>Ergebnisse</h3>
<?php $res=$db->query("SELECT r.*, s.name as sname FROM results r JOIN stations s ON r.station_id=s.id WHERE r.participant_id='{$t['custom_id']}' OR r.participant_id='$tid'");
while($r=$res->fetchArray()): ?>
<form method="post" class="card" style="border-left:5px solid #3498db;"><input type="hidden" name="rid" value="<?=$r['id']?>"><strong><?=$r['sname']?></strong>
<div style="display:flex;gap:5px;"><input type="number" name="b" value="<?=$r['bottles']?>">🍺 <input type="number" name="p" value="<?=$r['points']?>">Pkt <input type="number" name="pp" value="<?=$r['penalty_points']?>">⚠️</div>
<input type="text" name="c" value="<?=$r['comment']?>"><button name="update_res" class="btn-success">Update</button></form>
<?php endwhile; ?>
