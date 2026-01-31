<?php
// 1. Logik: Neues Team hinzufügen
if (isset($_POST['add_team'])) {
    $stmt = $db->prepare("INSERT INTO participants (teamname, custom_id, p1, p2, p3, p4) VALUES (:n, :cid, :p1, :p2, :p3, :p4)");
    $stmt->bindValue(':n', $_POST['teamname']);
    $stmt->bindValue(':cid', $_POST['custom_id']);
    $stmt->bindValue(':p1', $_POST['p1']);
    $stmt->bindValue(':p2', $_POST['p2']);
    $stmt->bindValue(':p3', $_POST['p3']);
    $stmt->bindValue(':p4', $_POST['p4']);
    
    if ($stmt->execute()) {
        echo "<div class='success'>Team erfolgreich registriert!</div>";
    }
}

// 2. Logik: Team löschen (inklusive aller Ergebnisse!)
if (isset($_GET['del_team'])) {
    $del_id = (int)$_GET['del_team'];
    
    // Zuerst Ergebnisse löschen (Datenbank-Integrität)
    $stmt_res = $db->prepare("DELETE FROM results WHERE participant_id = (SELECT custom_id FROM participants WHERE id = :id) OR participant_id = CAST(:id AS TEXT)");
    $stmt_res->bindValue(':id', $del_id);
    $stmt_res->execute();
    
    // Dann das Team selbst
    $db->exec("DELETE FROM participants WHERE id = $del_id");
    echo "<div class='success' style='background:#f8d7da; color:#721c24;'>Team und alle zugehörigen Ergebnisse wurden gelöscht.</div>";
}

// 3. Teams laden
$res = $db->query("SELECT * FROM participants ORDER BY id DESC");
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <h2>👥 Team-Verwaltung</h2>
    <button onclick="document.getElementById('add-form').style.display='block'" class="btn-primary">+ Neues Team</button>
</div>

<div id="add-form" class="card" style="display:none; border-top: 5px solid var(--accent);">
    <h3>Neues Team registrieren</h3>
    <form method="post">
        <div style="display:grid; grid-template-columns: 2fr 1fr; gap:15px; margin-bottom:15px;">
            <label>Teamname:<br><input type="text" name="teamname" required placeholder="z.B. Die Hopfenjäger"></label>
            <label>Startnummer / QR-ID:<br><input type="text" name="custom_id" required placeholder="z.B. 101"></label>
        </div>
        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap:10px;">
            <input type="text" name="p1" placeholder="Name 1">
            <input type="text" name="p2" placeholder="Name 2">
            <input type="text" name="p3" placeholder="Name 3">
            <input type="text" name="p4" placeholder="Name 4">
        </div>
        <div style="margin-top:15px; display:flex; gap:10px;">
            <button name="add_team" class="btn-success" style="flex:2;">Team speichern</button>
            <button type="button" onclick="document.getElementById('add-form').style.display='none'" class="btn-primary" style="background:#7f8c8d;">Abbrechen</button>
        </div>
    </form>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Teamname</th>
                <th>Mitglieder</th>
                <th style="text-align:right;">Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php while($t = $res->fetchArray(SQLITE3_ASSOC)): ?>
            <tr>
                <td><span class="badge" style="background:#eee; padding:2px 6px; border-radius:4px; font-size:0.8em;"><?= htmlspecialchars($t['custom_id']) ?></span></td>
                <td><strong><?= htmlspecialchars($t['teamname']) ?></strong></td>
                <td>
                    <small style="color:#666;">
                        <?= implode(", ", array_filter([$t['p1'], $t['p2'], $t['p3'], $t['p4']])) ?>
                    </small>
                </td>
                <td style="text-align:right;">
                    <a href="index.php?module=team_edit&id=<?= $t['id'] ?>" class="btn-primary" style="padding:5px 10px; font-size:0.8rem; text-decoration:none; background:#2980b9;">Öffnen / Akte</a>
                    <a href="index.php?module=participants_manage&del_team=<?= $t['id'] ?>" class="btn-danger" style="padding:5px 10px; font-size:0.8rem; text-decoration:none;" onclick="return confirm('ACHTUNG: Dies löscht das Team UND alle bereits erfassten Ergebnisse an den Stationen!')">Löschen</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
