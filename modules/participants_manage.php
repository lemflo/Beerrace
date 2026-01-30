<?php
if (isset($_POST['add_p'])) {
    $stmt = $db->prepare("INSERT INTO participants (teamname, p1, p2, custom_id) VALUES (:n, :p1, :p2, :c)");
    $stmt->bindValue(':n', $_POST['tname']);
    $stmt->bindValue(':p1', $_POST['p1']);
    $stmt->bindValue(':p2', $_POST['p2']);
    $stmt->bindValue(':c', $_POST['cid']);
    $stmt->execute();
    echo "<div class='success'>✅ Team registriert!</div>";
}
if (isset($_GET['del_p'])) {
    $db->exec("DELETE FROM participants WHERE id = " . (int)$_GET['del_p']);
}
?>

<h2>Teilnehmer-Verwaltung</h2>
<section class="card">
    <h3>Neues Team anlegen</h3>
    <form method="post">
        <div style="display:flex; gap:5px;">
            <input type="text" id="p_cid" name="cid" placeholder="QR-Code ID scannen oder tippen">
            <button type="button" onclick="startAdminScan()" class="btn-primary">📸 Scan</button>
        </div>
        <div id="p-reader" style="display:none; margin:10px 0;"></div>
        
        <input type="text" name="tname" placeholder="Team-Name" required>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
            <input type="text" name="p1" placeholder="Teilnehmer 1">
            <input type="text" name="p2" placeholder="Teilnehmer 2">
        </div>
        <button type="submit" name="add_p" class="btn-primary" style="width:100%; margin-top:10px;">Team Speichern</button>
    </form>
</section>

<section class="card">
    <h3>Eingeschriebene Teams</h3>
    <table>
        <thead><tr><th>ID</th><th>Teamname</th><th>Mitglieder</th><th>Aktion</th></tr></thead>
        <?php
        $res = $db->query("SELECT * FROM participants ORDER BY id DESC");
        while($p = $res->fetchArray()) {
            echo "<tr>
                    <td><code>".htmlspecialchars($p['custom_id'])."</code></td>
                    <td>".htmlspecialchars($p['teamname'])."</td>
                    <td><small>".htmlspecialchars($p['p1'])." & ".htmlspecialchars($p['p2'])."</small></td>
                    <td><a href='index.php?module=participants_manage&del_p={$p['id']}' class='btn-danger'>Löschen</a></td>
                  </tr>";
        }
        ?>
    </table>
</section>

<script>
function startAdminScan() {
    const r = document.getElementById('p-reader'); r.style.display='block';
    const s = new Html5QrcodeScanner("p-reader", {fps:10, qrbox:200});
    s.render(t => { document.getElementById('p_cid').value=t; s.clear(); r.style.display='none'; });
}
</script>
