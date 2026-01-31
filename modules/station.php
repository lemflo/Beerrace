<?php
// 1. Stationswahl Logik
if (isset($_POST['change_station'])) {
    unset($_SESSION['current_station']);
}

if (isset($_POST['set_stat'])) {
    $_SESSION['current_station'] = $_POST['stat_id'];
}

$sel_stat = $_SESSION['current_station'] ?? null;

// 2. Check-in Logik (Auto-Check-in)
if (isset($_POST['do_checkin'])) {
    $pid = $_POST['participant_id'];
    $sid = $sel_stat;
    $uid = $_SESSION['user_id'];

    // Prüfen, ob das Team bereits an dieser Station aktiv ist (Doppel-Scan verhindern)
    $exists = $db->querySingle("SELECT COUNT(*) FROM results WHERE participant_id = '$pid' AND station_id = $sid AND status = 'active'");
    
    if ($exists == 0) {
        $stmt = $db->prepare("INSERT INTO results (participant_id, station_id, user_id, checkin_at, status) VALUES (:pid, :sid, :uid, datetime('now'), 'active')");
        $stmt->bindValue(':pid', $pid);
        $stmt->bindValue(':sid', $sid);
        $stmt->bindValue(':uid', $uid);
        $stmt->execute();
    }
}

// 3. Abbruch Logik
if (isset($_POST['cancel_checkin'])) {
    $rid = (int)$_POST['result_id'];
    $db->exec("DELETE FROM results WHERE id = $rid");
}

// 4. Check-out Logik (Speichern)
if (isset($_POST['save_checkout'])) {
    $rid = (int)$_POST['result_id'];
    $stype = $_POST['stype'];
    $ts = 0;

    // Zeitberechnung basierend auf Typ
    if ($stype == 'time') {
        $ts = ((int)$_POST['min'] * 60) + (int)$_POST['sec'];
    } elseif (in_array($stype, ['start_time', 'end_time'])) {
        $parts = explode(':', $_POST['uhrzeit']);
        $ts = ($parts[0] * 3600) + ($parts[1] * 60);
    }

    $stmt = $db->prepare("UPDATE results SET 
        checkout_at = datetime('now'), 
        points = :p, 
        penalty_points = :pp, 
        bottles = :b, 
        comment = :c, 
        time_seconds = :ts, 
        status = 'done' 
        WHERE id = :rid");
        
    $stmt->bindValue(':p', (int)$_POST['points']);
    $stmt->bindValue(':pp', (int)$_POST['penalty_points']);
    $stmt->bindValue(':b', (int)$_POST['bottles']);
    $stmt->bindValue(':c', $_POST['comment']);
    $stmt->bindValue(':ts', $ts);
    $stmt->bindValue(':rid', $rid);
    $stmt->execute();
}

// --- VIEW ---

if (!$sel_stat): ?>
    <div class="card">
        <h3>📍 Erfassung: Station wählen</h3>
        <p>Wähle die Station aus, an der du gerade arbeitest.</p>
        <form method="post">
            <select name="stat_id" style="font-size: 1.2rem; padding: 15px; margin-bottom: 20px;">
                <?php 
                // HIER: Sortierung nach sort_order
                $res = $db->query("SELECT * FROM stations ORDER BY sort_order ASC");
                while($r = $res->fetchArray(SQLITE3_ASSOC)) {
                    echo "<option value='{$r['id']}'>{$r['name']} (" . ucfirst($r['type']) . ")</option>";
                }
                ?>
            </select>
            <button name="set_stat" class="btn-primary" style="width:100%; padding: 20px;">Station öffnen</button>
        </form>
    </div>

<?php else: 
    // Details der gewählten Station holen
    $st = $db->query("SELECT * FROM stations WHERE id = $sel_stat")->fetchArray(SQLITE3_ASSOC);
    
    // Prüfen, ob gerade ein Team eingecheckt ist
    $active = $db->query("SELECT r.*, p.teamname FROM results r 
                         LEFT JOIN participants p ON (r.participant_id = p.custom_id OR r.participant_id = CAST(p.id AS TEXT)) 
                         WHERE r.station_id = $sel_stat AND r.status = 'active'")->fetchArray(SQLITE3_ASSOC);
?>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
        <h2>Station: <?= htmlspecialchars($st['name']) ?></h2>
        <?php if (!$active): ?>
            <form method="post"><button name="change_station" class="btn-danger" style="font-size:0.8rem; padding:5px 10px;">Station wechseln</button></form>
        <?php endif; ?>
    </div>

    <?php if (!$active): ?>
        <div class="card" style="text-align:center; border: 2px dashed #ccc;">
            <h4>Bereit für Check-in</h4>
            <p>Scanne einen QR-Code oder gib die Team-ID ein.</p>
            
            <div style="display:flex; gap:10px; margin-bottom:15px;">
                <input type="text" id="p_id" placeholder="QR-Code / ID..." oninput="handleInput(this.value)" autofocus style="text-align:center; font-size:1.5rem;">
                <button type="button" onclick="startScan()" class="btn-primary" style="padding:0 20px;">📸</button>
            </div>

            <div id="reader" style="display:none; margin-bottom:20px;"></div>
            
            <div id="hist" style="display:none; padding:15px; background:#e3f2fd; border-radius:8px; border:1px solid #2196f3;">
                </div>

            <form method="post" id="checkin_form">
                <input type="hidden" name="participant_id" id="f_pid">
                <input type="hidden" name="do_checkin" value="1">
            </form>
        </div>

    <?php else: ?>
        <form method="post" class="card" style="border-left: 10px solid var(--success);">
            <h3 style="margin-bottom:5px;">Eingecheckt: <?= htmlspecialchars($active['teamname']) ?></h3>
            <p style="color:#666; font-size:0.9rem; margin-bottom:20px;">Check-in Zeit: <?= date('H:i:s', strtotime($active['checkin_at'])) ?></p>
            
            <input type="hidden" name="result_id" value="<?= $active['id'] ?>">
            <input type="hidden" name="stype" value="<?= $st['type'] ?>">
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:20px;">
                <label>🍺 Getrunkene Biere:<br><input type="number" name="bottles" value="0" min="0"></label>
                <label>⚠️ Strafpunkte:<br><input type="number" name="penalty_points" value="0" min="0"></label>
            </div>

            <div style="background:#f9f9f9; padding:15px; border-radius:8px; margin-bottom:20px;">
                <?php if($st['type'] == 'time'): ?>
                    <label><strong>Parcours-Zeit:</strong></label>
                    <div style="display:flex; align-items:center; gap:10px; margin-top:5px;">
                        <input type="number" name="min" placeholder="Min" style="width:80px;"> : 
                        <input type="number" name="sec" placeholder="Sek" style="width:80px;">
                    </div>
                <?php elseif(in_array($st['type'], ['start_time', 'end_time'])): ?>
                    <label><strong>Durchgangs-Uhrzeit:</strong></label>
                    <input type="time" name="uhrzeit" value="<?= date('H:i') ?>" style="margin-top:5px;">
                <?php else: ?>
                    <label><strong>Erreichte Punkte:</strong></label>
                    <input type="number" name="points" value="0" style="margin-top:5px;">
                <?php endif; ?>
            </div>

            <label>Interne Bemerkung:</label>
            <textarea name="comment" placeholder="Besonderheiten?" rows="2"></textarea>
            
            <div style="display:grid; grid-template-columns: 1fr 2fr; gap:15px; margin-top:20px;">
                <button name="cancel_checkin" class="btn-danger" onclick="return confirm('Soll der Check-in wirklich abgebrochen werden? Alle Daten gehen verloren.')">Abbruch</button>
                <button name="save_checkout" class="btn-success" style="font-size:1.1rem;">Ergebnis speichern & Check-out</button>
            </div>
        </form>
    <?php endif; ?>

    <script>
    let isSubmitting = false;

    function handleInput(id) {
        if(id.length < 1) return;
        
        const f_pid = document.getElementById('f_pid');
        const hist = document.getElementById('hist');
        f_pid.value = id;

        // AJAX Lookup für das Team (optional zur Info)
        fetch('api/team_lookup.php?id=' + id)
        .then(r => r.json())
        .then(d => {
            if(d.found && !isSubmitting) {
                isSubmitting = true;
                hist.style.display = 'block';
                hist.innerHTML = `<strong>Check-in für Team: ${d.teamname}</strong><br>Bitte warten...`;
                
                // Kurze Verzögerung für visuelles Feedback, dann Submit
                setTimeout(() => {
                    document.getElementById('checkin_form').submit();
                }, 400);
            }
        });
    }

    function startScan() {
        const readerDiv = document.getElementById('reader');
        readerDiv.style.display = 'block';
        
        const html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
        html5QrcodeScanner.render((decodedText) => {
            document.getElementById('p_id').value = decodedText;
            handleInput(decodedText);
            html5QrcodeScanner.clear();
            readerDiv.style.display = 'none';
        });
    }
    </script>
<?php endif; ?>
