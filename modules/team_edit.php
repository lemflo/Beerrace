<?php
$tid = (int)$_GET['id'];

if (isset($_GET['del_res'])) {
    $db->exec("DELETE FROM results WHERE id = " . (int)$_GET['del_res']);
}

if (isset($_POST['update_team'])) {
    $stmt = $db->prepare("UPDATE participants SET teamname=:n, p1=:p1, p2=:p2, p3=:p3, p4=:p4, custom_id=:cid, global_comment=:gc, time_adjustment=:ta WHERE id=:id");
    $stmt->bindValue(':n', $_POST['tname']); $stmt->bindValue(':p1', $_POST['p1']); $stmt->bindValue(':p2', $_POST['p2']);
    $stmt->bindValue(':p3', $_POST['p3']); $stmt->bindValue(':p4', $_POST['p4']); $stmt->bindValue(':cid', $_POST['cid']);
    $stmt->bindValue(':gc', $_POST['global_comment']); $stmt->bindValue(':ta', (int)$_POST['time_adjustment']); $stmt->bindValue(':id', $tid);
    $stmt->execute();
}

if (isset($_POST['update_res'])) {
    $ts = 0;
    if ($_POST['stype'] == 'time') $ts = (int)$_POST['min']*60 + (int)$_POST['sec'];
    elseif (in_array($_POST['stype'],['start_time','end_time'])) { $p=explode(':',$_POST['uhrzeit']); $ts=($p[0]*3600)+($p[1]*60); }
    $stmt = $db->prepare("UPDATE results SET bottles=:b, points=:p, penalty_points=:pp, comment=:c, time_seconds=:ts WHERE id=:rid");
    $stmt->bindValue(':b',(int)$_POST['b']); $stmt->bindValue(':p',(int)$_POST['p']); $stmt->bindValue(':pp',(int)$_POST['pp']);
    $stmt->bindValue(':c',$_POST['c']); $stmt->bindValue(':ts',$ts); $stmt->bindValue(':rid',(int)$_POST['rid']); $stmt->execute();
}

$t = $db->query("SELECT * FROM participants WHERE id=$tid")->fetchArray(SQLITE3_ASSOC);
?>
<h2>Akte: <?=htmlspecialchars($t['teamname'])?></h2>

<form method="post" class="card" style="border-top:5px solid #e67e22;">
    <div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px;">
        <div>
            <label>Teamname & ID:</label>
            <div style="display:flex; gap:5px; margin-bottom:10px;">
                <input type="text" name="tname" value="<?=$t['teamname']?>" style="flex:2;">
                <input type="text" name="cid" value="<?=$t['custom_id']?>" style="flex:1;">
            </div>
            <label>Teilnehmer (1-4):</label>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:5px;">
                <input type="text" name="p1" value="<?=$t['p1']?>"> <input type="text" name="p2" value="<?=$t['p2']?>">
                <input type="text" name="p3" value="<?=$t['p3']?>"> <input type="text" name="p4" value="<?=$t['p4']?>">
            </div>
        </div>
        <div style="background:#fff3cd; padding:10px; border-radius:5px;">
            <label><strong>⏱️ Zeit-Korrektur (Sek):</strong></label>
            <input type="number" name="time_adjustment" value="<?=$t['time_adjustment']?>" style="width:100%; font-size:1.2em;">
            <small>+60 = Strafe<br>-60 = Bonus</small>
        </div>
    </div>
    <label style="display:block; margin-top:10px;">Globale Anmerkung:</label>
    <textarea name="global_comment" rows="2" style="width:100%;"><?=$t['global_comment']?></textarea>
    <button name="update_team" class="btn-primary" style="width:100%; margin-top:10px;">Stammdaten speichern</button>
</form>

<h3>Stations-Ergebnisse</h3>
<?php 
$res = $db->query("SELECT r.*, s.name as sname, s.type as stype FROM results r JOIN stations s ON r.station_id=s.id WHERE (r.participant_id='{$t['custom_id']}' OR r.participant_id='$tid') ORDER BY s.sort_order ASC");
while($r = $res->fetchArray()): ?>
<form method="post" class="card">
    <input type="hidden" name="rid" value="<?=$r['id']?>"> <input type="hidden" name="stype" value="<?=$r['stype']?>">
    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
        <strong><?=$r['sname']?></strong>
        <a href="index.php?module=team_edit&id=<?=$tid?>&del_res=<?=$r['id']?>" style="color:red;" onclick="return confirm('Löschen?')">Ergebnis entfernen</a>
    </div>
    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:10px;">
        <label>Bier:<input type="number" name="b" value="<?=$r['bottles']?>"></label>
        <label>Punkte:<input type="number" name="p" value="<?=$r['points']?>"></label>
        <label>Strafen:<input type="number" name="pp" value="<?=$r['penalty_points']?>"></label>
    </div>
    <?php if ($r['stype'] == 'time'): ?>
        <div style="margin-top:10px;">Zeit: <input type="number" name="min" value="<?=floor($r['time_seconds']/60)?>" style="width:50px;">:<input type="number" name="sec" value="<?=$r['time_seconds']%60?>" style="width:50px;"></div>
    <?php elseif (in_array($r['stype'],['start_time','end_time'])): ?>
        <div style="margin-top:10px;">Uhrzeit: <input type="time" name="uhrzeit" value="<?=gmdate("H:i",$r['time_seconds'])?>"></div>
    <?php endif; ?>
    <button name="update_res" class="btn-success" style="margin-top:10px;">Update Station</button>
</form>
<?php endwhile; ?>
