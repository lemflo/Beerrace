<?php
if (isset($_POST['change_station'])) unset($_SESSION['current_station']);
if (isset($_POST['set_stat'])) $_SESSION['current_station'] = $_POST['stat_id'];
$sel_stat = $_SESSION['current_station'] ?? null;

if (isset($_POST['do_checkin'])) {
    $stmt = $db->prepare("INSERT INTO results (participant_id, station_id, user_id, checkin_at, status) VALUES (:pid, :sid, :uid, datetime('now'), 'active')");
    $stmt->bindValue(':pid', $_POST['participant_id']); $stmt->bindValue(':sid', $sel_stat); $stmt->bindValue(':uid', $_SESSION['user_id']); $stmt->execute();
}

if (isset($_POST['save_checkout'])) {
    $ts = 0; $p = (int)($_POST['points'] ?? 0);
    if ($_POST['stype'] == 'time') $ts = (int)$_POST['min']*60 + (int)$_POST['sec'];
    elseif (in_array($_POST['stype'], ['start_time','end_time'])) { $parts = explode(':', $_POST['uhrzeit']); $ts = ($parts[0]*3600)+($parts[1]*60); }
    $stmt = $db->prepare("UPDATE results SET checkout_at=datetime('now'), points=:p, penalty_points=:pp, bottles=:b, comment=:c, type=:ty, time_seconds=:ts, status='done' WHERE id=:rid");
    $stmt->bindValue(':p', $p); $stmt->bindValue(':pp', (int)$_POST['penalty_points']); $stmt->bindValue(':b', (int)$_POST['bottles']); $stmt->bindValue(':c', $_POST['comment']); $stmt->bindValue(':ty', $_POST['stype']); $stmt->bindValue(':ts', $ts); $stmt->bindValue(':rid', $_POST['result_id']); $stmt->execute();
    echo "<div class='success'>Gespeichert!</div>";
}

if (!$sel_stat): ?>
    <form method="post"><select name="stat_id"><?php $res=$db->query("SELECT * FROM stations"); while($r=$res->fetchArray()) echo "<option value='{$r['id']}'>{$r['name']}</option>"; ?></select>
    <button name="set_stat" class="btn-primary" style="width:100%;">Station betreten</button></form>
<?php else: 
    $st = $db->query("SELECT * FROM stations WHERE id=$sel_stat")->fetchArray(SQLITE3_ASSOC);
    $active = $db->query("SELECT r.*, p.teamname FROM results r LEFT JOIN participants p ON (r.participant_id=p.custom_id OR r.participant_id=p.id) WHERE r.station_id=$sel_stat AND r.status='active'")->fetchArray(SQLITE3_ASSOC);
?>
    <div style="display:flex;justify-content:space-between;"><h3>📍 <?=$st['name']?></h3><form method="post"><button name="change_station" class="btn-danger">Wechseln</button></form></div>
    <?php if (!$active): ?>
        <div class="card"><div style="display:flex;gap:5px;"><input type="text" id="p_id" placeholder="Team ID..." oninput="loadHist(this.value)"><button type="button" onclick="startScan()" class="btn-primary">📸</button></div>
        <div id="reader" style="display:none;margin:10px 0;"></div><div id="hist" style="display:none;background:#e3f2fd;padding:10px;margin-top:10px;border-radius:5px;"></div>
        <form method="post" style="margin-top:10px;"><input type="hidden" name="participant_id" id="f_pid"><button name="do_checkin" class="btn-success">Check-in</button></form></div>
    <?php else: ?>
        <form method="post" class="card"><h4>Check-out: <?=$active['teamname']?></h4><input type="hidden" name="result_id" value="<?=$active['id']?>"><input type="hidden" name="stype" value="<?=$st['type']?>">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;"><label>🍺 Bier<input type="number" name="bottles" value="0"></label><label>⚠️ Strafen<input type="number" name="penalty_points" value="0"></label></div>
        <?php if($st['type']=='points'): ?><label>Punkte</label><input type="number" name="points">
        <?php elseif($st['type']=='time'): ?><label>Zeit</label><div style="display:flex;gap:5px;"><input type="number" name="min" placeholder="Min">:<input type="number" name="sec" placeholder="Sek"></div>
        <?php elseif(strpos($st['type'], 'time')!==false): ?><label>Uhrzeit</label><input type="time" name="uhrzeit" value="<?=date('H:i')?>"><?php endif; ?>
        <textarea name="comment" placeholder="Kommentar..."></textarea><button name="save_checkout" class="btn-success">Check-out</button></form>
    <?php endif; ?>
    <script>
    function loadHist(id){ document.getElementById('f_pid').value=id; if(id.length<1)return; fetch('api/team_lookup.php?id='+id).then(r=>r.json()).then(d=>{ if(d.found){ const b=document.getElementById('hist'); b.style.display='block'; b.innerHTML=`<strong>${d.teamname}</strong><br>Bier gesamt: ${d.total_bottles}`; }});}
    function startScan(){ const r=document.getElementById('reader'); r.style.display='block'; const s=new Html5QrcodeScanner("reader",{fps:10,qrbox:200}); s.render(t=>{document.getElementById('p_id').value=t;loadHist(t);s.clear();r.style.display='none';});}
    </script>
<?php endif; ?>
