<?php
if (isset($_POST['change_station'])) unset($_SESSION['current_station']);
if (isset($_POST['set_stat'])) $_SESSION['current_station'] = $_POST['stat_id'];
$sel_stat = $_SESSION['current_station'] ?? null;

if (isset($_POST['do_checkin'])) {
    $stmt = $db->prepare("INSERT INTO results (participant_id, station_id, user_id, checkin_at, status) VALUES (:pid, :sid, :uid, datetime('now'), 'active')");
    $stmt->bindValue(':pid', $_POST['participant_id']); $stmt->bindValue(':sid', $sel_stat); $stmt->bindValue(':uid', $_SESSION['user_id']); $stmt->execute();
}

if (isset($_POST['cancel_checkin'])) {
    $db->exec("DELETE FROM results WHERE id = " . (int)$_POST['result_id']);
}

if (isset($_POST['save_checkout'])) {
    $ts = 0;
    if ($_POST['stype'] == 'time') $ts = (int)$_POST['min']*60 + (int)$_POST['sec'];
    elseif (in_array($_POST['stype'], ['start_time','end_time'])) {
        $parts = explode(':', $_POST['uhrzeit']); $ts = ($parts[0]*3600)+($parts[1]*60);
    }
    $stmt = $db->prepare("UPDATE results SET checkout_at=datetime('now'), points=:p, penalty_points=:pp, bottles=:b, comment=:c, type=:ty, time_seconds=:ts, status='done' WHERE id=:rid");
    $stmt->bindValue(':p', (int)$_POST['points']); $stmt->bindValue(':pp', (int)$_POST['penalty_points']); $stmt->bindValue(':b', (int)$_POST['bottles']);
    $stmt->bindValue(':c', $_POST['comment']); $stmt->bindValue(':ty', $_POST['stype']); $stmt->bindValue(':ts', $ts); $stmt->bindValue(':rid', $_POST['result_id']);
    $stmt->execute();
}

if (!$sel_stat): ?>
    <form method="post" class="card">
        <h3>Station wählen</h3>
        <select name="stat_id" style="width:100%; margin-bottom:10px;">
            <?php $res=$db->query("SELECT * FROM stations ORDER BY sort_order ASC"); while($r=$res->fetchArray()) echo "<option value='{$r['id']}'>{$r['name']}</option>"; ?>
        </select>
        <button name="set_stat" class="btn-primary" style="width:100%;">Station öffnen</button>
    </form>
<?php else: 
    $st = $db->query("SELECT * FROM stations WHERE id=$sel_stat")->fetchArray(SQLITE3_ASSOC);
    $active = $db->query("SELECT r.*, p.teamname FROM results r LEFT JOIN participants p ON (r.participant_id=p.custom_id OR r.participant_id=CAST(p.id AS TEXT)) WHERE r.station_id=$sel_stat AND r.status='active'")->fetchArray(SQLITE3_ASSOC);
?>
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2>📍 <?=$st['name']?></h2>
        <?php if (!$active): ?>
            <form method="post"><button name="change_station" class="btn-danger" style="font-size:0.7em;">Wechseln</button></form>
        <?php endif; ?>
    </div>

    <?php if (!$active): ?>
        <div class="card">
            <h4>Check-in</h4>
            <div style="display:flex; gap:5px;">
                <input type="text" id="p_id" placeholder="ID scannen..." oninput="handleInput(this.value)" autofocus>
                <button type="button" onclick="startScan()" class="btn-primary">📸</button>
            </div>
            <div id="reader" style="display:none; margin:10px 0;"></div>
            <div id="hist" style="display:none; padding:10px; background:#e3f2fd; border-radius:5px; margin-top:10px;"></div>
            <form method="post" id="checkin_form">
                <input type="hidden" name="participant_id" id="f_pid">
                <input type="hidden" name="do_checkin" value="1">
            </form>
        </div>
    <?php else: ?>
        <form method="post" class="card">
            <h3>Check-out: <?=$active['teamname']?></h3>
            <input type="hidden" name="result_id" value="<?=$active['id']?>">
            <input type="hidden" name="stype" value="<?=$st['type']?>">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:10px;">
                <label>Bier:<br><input type="number" name="bottles" value="0"></label>
                <label>Strafen:<br><input type="number" name="penalty_points" value="0"></label>
            </div>

            <?php if($st['type']=='time'): ?>
                <label>Zeit:</label>
                <div style="display:flex; gap:5px;"><input type="number" name="min" placeholder="Min">:<input type="number" name="sec" placeholder="Sek"></div>
            <?php elseif(strpos($st['type'],'time')!==false): ?>
                <label>Uhrzeit:</label><input type="time" name="uhrzeit" value="<?=date('H:i')?>">
            <?php else: ?>
                <label>Punkte:</label><input type="number" name="points" value="0">
            <?php endif; ?>

            <textarea name="comment" placeholder="Anmerkung..." style="width:100%; margin:10px 0;"></textarea>
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                <button name="cancel_checkin" class="btn-danger" onclick="return confirm('Abbrechen?')">Abbruch</button>
                <button name="save_checkout" class="btn-success">Speichern</button>
            </div>
        </form>
    <?php endif; ?>

    <script>
    let isSubmitting = false;
    function handleInput(id) {
        if(id.length < 1) return;
        document.getElementById('f_pid').value = id;
        fetch('api/team_lookup.php?id=' + id).then(r => r.json()).then(d => {
            if(d.found && !isSubmitting) {
                isSubmitting = true;
                document.getElementById('hist').style.display = 'block';
                document.getElementById('hist').innerHTML = `<strong>Team: ${d.teamname}</strong><br>Bier gesamt: ${d.total_bottles}`;
                setTimeout(() => document.getElementById('checkin_form').submit(), 500);
            }
        });
    }
    function startScan() {
        const r = document.getElementById('reader'); r.style.display='block';
        const s = new Html5QrcodeScanner("reader", {fps:10, qrbox:200});
        s.render(t => { document.getElementById('p_id').value=t; handleInput(t); s.clear(); r.style.display='none'; });
    }
    </script>
<?php endif; ?>
