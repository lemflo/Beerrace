<?php
$sel_stat = $_SESSION['current_station'] ?? null;

// Station für die aktuelle Session festlegen
if (isset($_POST['set_stat'])) {
    $sel_stat = $_SESSION['current_station'] = $_POST['stat_id'];
}

// Ergebnis speichern
if (isset($_POST['save'])) {
    $stmt = $db->prepare("INSERT INTO results (participant_id, station_id, user_id, points, time_seconds, bottles, type) VALUES (:pid, :sid, :uid, :p, :t, :b, :ty)");
    $stmt->bindValue(':pid', $_POST['participant_id']);
    $stmt->bindValue(':sid', $sel_stat);
    $stmt->bindValue(':uid', $_SESSION['user_id']);
    $stmt->bindValue(':p', (int)$_POST['points']);
    $stmt->bindValue(':t', (int)$_POST['min']*60 + (int)$_POST['sec']);
    $stmt->bindValue(':b', (int)$_POST['bottles']);
    $stmt->bindValue(':ty', $_POST['type']);
    $stmt->execute();
    echo "<div class='success'>✅ Gespeichert: Team {$_POST['participant_id']} hat {$_POST['bottles']} Flaschen geleert!</div>";
}

if (!$sel_stat): 
?>
    <h2>Station auswählen</h2>
    <form method="post">
        <select name="stat_id" required>
            <option value="">-- Bitte wählen --</option>
            <?php 
            $res = $db->query("SELECT * FROM stations"); 
            while($r=$res->fetchArray()) echo "<option value='{$r['id']}'>{$r['name']}</option>"; 
            ?>
        </select>
        <button type="submit" name="set_stat" class="btn-primary">Station öffnen</button>
    </form>
<?php else: 
    $s_name = $db->querySingle("SELECT name FROM stations WHERE id = $sel_stat");
?>
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h3>Aktiv: <?php echo htmlspecialchars($s_name); ?></h3>
        <a href="modules/station_reset.php" style="color:red; font-size:0.8em;">Wechseln</a>
    </div>

    <div id="reader" style="width: 100%; border-radius: 8px; overflow:hidden;"></div>
    
    <form method="post" style="margin-top:15px;">
        <label>Team-ID (Scan oder Manuell):</label>
        <input type="text" id="p_id" name="participant_id" placeholder="QR scannen oder eingeben" required>
        
        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border: 1px solid #ffeeba; margin-bottom: 15px;">
            <label><strong>🍺 Geleerte Flaschen:</strong></label>
            <input type="number" name="bottles" value="0" min="0" style="font-size: 1.2rem; font-weight:bold;">
        </div>

        <label>Ergebnistyp:</label>
        <select name="type" id="type_select" onchange="updateView()">
            <option value="points">Punkte sammeln</option>
            <option value="time">Zeit stoppen</option>
        </select>

        <div id="d_p">
            <label>Punkte:</label>
            <input type="number" name="points" placeholder="Anzahl Punkte">
        </div>

        <div id="d_t" style="display:none;">
            <label>Zeit (Min:Sek):</label>
            <div style="display:flex; gap:10px; align-items:center;">
                <input type="number" name="min" placeholder="Min">
                <span>:</span>
                <input type="number" name="sec" placeholder="Sek">
            </div>
        </div>

        <button type="submit" name="save" class="btn-success" style="margin-top:20px; height: 60px; font-size: 1.2rem;">ERGEBNIS SPEICHERN</button>
    </form>

    <script>
        // Scanner Initialisierung
        const html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
        html5QrcodeScanner.render((text) => {
            document.getElementById('p_id').value = text;
            document.getElementById('p_id').style.borderColor = "#27ae60";
        });

        // Toggle Ansicht
        function updateView() {
            const val = document.getElementById('type_select').value;
            document.getElementById('d_p').style.display = (val === 'points' ? 'block' : 'none');
            document.getElementById('d_t').style.display = (val === 'time' ? 'flex' : 'none');
        }
    </script>
<?php endif; ?>
