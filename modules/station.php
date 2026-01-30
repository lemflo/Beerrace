<?php
$sel_stat = $_SESSION['current_station'] ?? null;
if (isset($_POST['set_stat'])) $sel_stat = $_SESSION['current_station'] = $_POST['stat_id'];

if (isset($_POST['do_checkin'])) {
    $stmt = $db->prepare("INSERT INTO results (participant_id, station_id, user_id, checkin_at, status) VALUES (:pid, :sid, :uid, datetime('now'), 'active')");
    $stmt->bindValue(':pid', $_POST['participant_id']);
    $stmt->bindValue(':sid', $sel_stat); $stmt->bindValue(':uid', $_SESSION['user_id']);
    $stmt->execute();
}

if (isset($_POST['save_checkout'])) {
    $stmt = $db->prepare("UPDATE results SET checkout_at = datetime('now'), points = :p, penalty_points = :pp, bottles = :b, comment = :c, type = :ty, status = 'done' WHERE id = :rid");
    $stmt->bindValue(':p', (int)$_POST['points']); $stmt->bindValue(':pp', (int)$_POST['penalty_points']);
    $stmt->bindValue(':b', (int)$_POST['bottles']); $stmt->bindValue(':c', $_POST['comment']);
    $stmt->bindValue(':ty', $_POST['type']); $stmt->bindValue(':rid', $_POST['result_id']);
    $stmt->execute();
    echo "<div class='success'>Check-out abgeschlossen!</div>";
}

if (!$sel_stat):
?>
    <h3>Station wählen</h3>
    <form method="post"><?php $res = $db->query("SELECT * FROM stations"); ?>
        <select name="stat_id"><?php while($r=$res->fetchArray()) echo "<option value='{$r['id']}'>{$r['name']}</option>"; ?></select>
        <button name="set_stat" class="btn-primary">Öffnen</button>
    </form>
<?php else: 
    $active = $db->query("SELECT r.*, p.teamname FROM results r LEFT JOIN participants p ON (r.participant_id = p.custom_id OR r.participant_id = CAST(p.id AS TEXT)) WHERE r.station_id = $sel_stat AND r.status = 'active'")->fetchArray(SQLITE3_ASSOC);
?>
    <div class="card">
        <h3><?php echo $db->querySingle("SELECT name FROM stations WHERE id=$sel_stat"); ?></h3>
        <?php if (!$active): ?>
            <label>Team-ID (Eingabe oder Scan):</label>
            <div style="display:flex; gap:5px;">
                <input type="text" id="p_id" placeholder="ID..." oninput="loadHistory(this.value)">
                <button type="button" onclick="startScan()" class="btn-primary">📸 Scan</button>
            </div>
            <div id="reader" style="display:none; margin:10px 0;"></div>
            <div id="history-box" style="display:none; background:#e3f2fd; padding:10px; margin-top:10px; border-radius:5px; max-height:150px; overflow-y:auto;"></div>
            <form method="post" style="margin-top:10px;">
                <input type="hidden" name="participant_id" id="form_p_id">
                <button name="do_checkin" class="btn-success">Check-in</button>
            </form>
        <?php else: ?>
            <h4>Check-out: <?php echo $active['teamname']; ?></h4>
            <form method="post">
                <input type="hidden" name="result_id" value="<?php echo $active['id']; ?>">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <label>Bier 🍺<input type="number" name="bottles" value="0"></label>
                    <label>Strafen ⚠️<input type="number" name="penalty_points" value="0"></label>
                </div>
                <select name="type"><option value="points">Punkte</option><option value="time">Zeit</option></select>
                <input type="number" name="points" placeholder="Ergebnis">
                <textarea name="comment" placeholder="Bemerkung..."></textarea>
                <button name="save_checkout" class="btn-success">Check-out & Speichern</button>
            </form>
        <?php endif; ?>
    </div>
    <script>
    function loadHistory(id) {
        document.getElementById('form_p_id').value = id;
        if(id.length < 1) return;
        fetch('api/team_lookup.php?id='+id).then(r=>r.json()).then(d=>{
            if(d.found){
                const b = document.getElementById('history-box'); b.style.display='block';
                b.innerHTML = `<strong>${d.teamname}</strong><br>Gesamt Bier: ${d.total_bottles}<br>${d.history.map(h=>`• ${h.sname} (${h.bottles}🍺)`).join('<br>')}`;
            }
        });
    }
    function startScan() {
        const r = document.getElementById('reader'); r.style.display='block';
        const s = new Html5QrcodeScanner("reader", {fps:10, qrbox:200});
        s.render(t=>{ document.getElementById('p_id').value=t; loadHistory(t); s.clear(); r.style.display='none'; });
    }
    </script>
<?php endif; ?>
