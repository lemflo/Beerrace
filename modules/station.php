<?php
$sel_stat = $_SESSION['current_station'] ?? null;
if (isset($_POST['set_stat'])) $sel_stat = $_SESSION['current_station'] = $_POST['stat_id'];
if (isset($_POST['save'])) {
    $stmt = $db->prepare("INSERT INTO results (participant_id, station_id, user_id, points, time_seconds, type) VALUES (:pid, :sid, :uid, :p, :t, :ty)");
    $stmt->bindValue(':pid', $_POST['participant_id']);
    $stmt->bindValue(':sid', $sel_stat);
    $stmt->bindValue(':uid', $_SESSION['user_id']);
    $stmt->bindValue(':p', (int)$_POST['points']);
    $stmt->bindValue(':t', (int)$_POST['min']*60 + (int)$_POST['sec']);
    $stmt->bindValue(':ty', $_POST['type']);
    $stmt->execute();
    echo "<p class='success'>Gespeichert!</p>";
}
if (!$sel_stat): 
?>
    <form method="post">
        <select name="stat_id"><?php 
            $res = $db->query("SELECT * FROM stations"); 
            while($r=$res->fetchArray()) echo "<option value='{$r['id']}'>{$r['name']}</option>"; 
        ?></select>
        <button name="set_stat" class="btn-primary">Station wählen</button>
    </form>
<?php else: ?>
    <h3>Station aktiv</h3>
    <div id="reader"></div>
    <form method="post">
        <input type="text" id="p_id" name="participant_id" placeholder="ID (Scan oder Manuell)" required>
        <select name="type" onchange="this.value=='time'? (d_p.style.display='none', d_t.style.display='flex') : (d_p.style.display='block', d_t.style.display='none')">
            <option value="points">Punkte</option><option value="time">Zeit</option>
        </select>
        <div id="d_p"><input type="number" name="points" placeholder="Punkte"></div>
        <div id="d_t" style="display:none;"><input type="number" name="min" placeholder="Min">:<input type="number" name="sec" placeholder="Sek"></div>
        <button name="save" class="btn-success">Speichern</button>
        <a href="modules/station_reset.php">Wechseln</a>
    </form>
    <script>
        new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 }).render(t => document.getElementById('p_id').value = t);
    </script>
<?php endif; ?>
