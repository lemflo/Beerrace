<?php
$tid = (int)$_GET['id'];

// 1. Daten laden
$t = $db->query("SELECT * FROM participants WHERE id=$tid")->fetchArray(SQLITE3_ASSOC);
if (!$t) {
    echo "<div class='card'><p>Team nicht gefunden.</p></div>";
    return;
}

// 2. Logik: Stammdaten aktualisieren
if (isset($_POST['update_team'])) {
    $stmt = $db->prepare("UPDATE participants SET 
        teamname=:n, p1=:p1, p2=:p2, p3=:p3, p4=:p4, 
        custom_id=:cid, global_comment=:gc, time_adjustment=:ta 
        WHERE id=:id");
    $stmt->bindValue(':n', $_POST['tname']);
    $stmt->bindValue(':p1', $_POST['p1']);
    $stmt->bindValue(':p2', $_POST['p2']);
    $stmt->bindValue(':p3', $_POST['p3']);
    $stmt->bindValue(':p4', $_POST['p4']);
    $stmt->bindValue(':cid', $_POST['cid']);
    $stmt->bindValue(':gc', $_POST['global_comment']);
    $stmt->bindValue(':ta', (int)$_POST['time_adjustment']);
    $stmt->bindValue(':id', $tid);
    $stmt->execute();
    header("Location: index.php?module=team_edit&id=$tid&msg=updated");
    exit;
}

// 3. Logik: Einzelergebnis löschen
if (isset($_GET['del_res'])) {
    $db->exec("DELETE FROM results WHERE id = " . (int)$_GET['del_res']);
    header("Location: index.php?module=team_edit&id=$tid");
    exit;
}

// 4. Logik: Einzelergebnis aktualisieren
if (isset($_POST['update_res'])) {
    $stmt = $db->prepare("UPDATE results SET 
        bottles=:b, points=:p, penalty_points=:pp, comment=:c 
        WHERE id=:rid");
    $stmt->bindValue(':b', (int)$_POST['b']);
    $stmt->bindValue(':p', (int)$_POST['p']);
    $stmt->bindValue(':pp', (int)$_POST['pp']);
    $stmt->bindValue(':c', $_POST['c']);
    $stmt->bindValue(':rid', (int)$_POST['rid']);
    $stmt->execute();
    header("Location: index.php?module=team_edit&id=$tid&msg=res_updated");
    exit;
}
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
    <h2>Team-Akte: <?= htmlspecialchars($t['teamname']) ?></h2>
    <a href="index.php?module=participants_manage" class="btn-primary" style="text-decoration:none; font-size:0.8rem; background:#7f8c8d;">Zurück zur Liste</a>
</div>

<?php if(isset($_GET['msg'])): ?>
    <div class="success" style="margin-bottom:20px; padding:10px;">Änderungen erfolgreich gespeichert!</div>
<?php endif; ?>

<form method="post" class="card" style="border-top:5px solid var(--primary);">
    <div style="display:grid; grid-template-columns: 2fr 1fr; gap:20px;">
        <div>
            <label>Teamname:</label>
            <input type="text" name="tname" value="<?= htmlspecialchars($t['teamname']) ?>" required>
            
            <label>Startnummer / QR-ID (custom_id):</label>
            <input type="text" name="cid" value="<?= htmlspecialchars($t['custom_id']) ?>" required>

            <label>Teilnehmer (1-4):</label>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px;">
                <input type="text" name="p1" value="<?= htmlspecialchars($t['p1']) ?>" placeholder="P1">
                <input type="text" name="p2" value="<?= htmlspecialchars($t['p2']) ?>" placeholder="P2">
                <input type="text" name="p3" value="<?= htmlspecialchars($t['p3']) ?>" placeholder="P3">
                <input type="text" name="p4" value="<?= htmlspecialchars($t['p4']) ?>" placeholder="P4">
            </div>
        </div>

        <div style="background:#fff3cd; padding:15px; border-radius:8px; border:1px solid #ffeeba;">
            <label><strong>⏱️ Zeit-Korrektur (Sekunden):</strong></label>
            <input type="number" name="time_adjustment" value="<?= $t['time_adjustment'] ?>" style="font-size:1.2rem; font-weight:bold;">
            <p style="font-size:0.75rem; color:#856404; margin-top:5px;">
                Positive Zahl = Strafe (Zeit +)<br>
                Negative Zahl = Bonus (Zeit -)
            </p>
        </div>
    </div>

    <label style="display:block; margin-top:15px;">Interne Team-Notiz:</label>
    <textarea name="global_comment" rows="2"><?= htmlspecialchars($t['global_comment']) ?></textarea>

    <button name="update_team" class="btn-primary" style="width:100%; margin-top:15px;">Stammdaten & Zeitkorrektur speichern</button>
</form>

<hr style="margin:30px 0; border:0; border-top:1px solid #ccc;">

<h3>Erfasste Stations-Ergebnisse</h3>

<?php 
// Abfrage der Ergebnisse inklusive User-Name (Helfer)
$res = $db->query("SELECT r.*, s.name as sname, u.name as helper_name 
                   FROM results r 
                   JOIN stations s ON r.station_id=s.id 
                   LEFT JOIN users u ON r.user_id = u.id
                   WHERE (r.participant_id='{$t['custom_id']}' OR r.participant_id='$tid') 
                   ORDER BY r.timestamp DESC");

$found = false;
while($r = $res->fetchArray(SQLITE3_ASSOC)): 
    $found = true;
?>
    <div class="card" style="margin-bottom:15px; padding:15px; font-size: 0.9rem; border-left: 4px solid #ddd;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; border-bottom:1px solid #f0f0f0; padding-bottom:8px;">
            <div>
                <strong style="font-size:1rem;">📍 <?= htmlspecialchars($r['sname']) ?></strong><br>
                <small style="color:#666;">
                    Checkout: <?= $r['checkout_at'] ? date('d.m.Y H:i:s', strtotime($r['checkout_at'])) : 'Unvollständig' ?>
                </small>
            </div>
            <div style="text-align:right;">
                <small>Helfer: <strong><?= htmlspecialchars($r['helper_name'] ?? 'System') ?></strong></small><br>
                <a href="index.php?module=team_edit&id=<?= $tid ?>&del_res=<?= $r['id'] ?>" 
                   style="color:var(--danger); font-size:0.75rem;" 
                   onclick="return confirm('Ergebnis dieser Station wirklich löschen?')">Löschen</a>
            </div>
        </div>

        <form method="post" style="display:grid; grid-template-columns: repeat(3, 1fr) 2fr auto; gap:10px; align-items:end;">
            <input type="hidden" name="rid" value="<?= $r['id'] ?>">
            
            <label>Bier:<input type="number" name="b" value="<?= $r['bottles'] ?>"></label>
            <label>Punkte:<input type="number" name="p" value="<?= $r['points'] ?>"></label>
            <label>Strafen:<input type="number" name="pp" value="<?= $r['penalty_points'] ?>"></label>
            
            <label>Kommentar:<input type="text" name="c" value="<?= htmlspecialchars($r['comment']) ?>"></label>
            
            <button name="update_res" class="btn-success" style="padding: 8px 12px;" title="Speichern">💾</button>
        </form>
    </div>
<?php endwhile; 

if(!$found) echo "<p style='color:#666; font-style:italic;'>Noch keine Ergebnisse für dieses Team vorhanden.</p>";
?>
