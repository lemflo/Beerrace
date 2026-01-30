<?php
if(isset($_POST['add_p'])) {
    $stmt = $db->prepare("INSERT INTO participants (teamname, custom_id) VALUES (:n, :c)");
    $stmt->bindValue(':n', $_POST['tname']);
    $stmt->bindValue(':c', $_POST['cid']);
    $stmt->execute();
}
if(isset($_GET['del_res'])) $db->exec("DELETE FROM results WHERE id=".(int)$_GET['del_res']);
?>
<h2>Admin</h2>
<div class="card">
    <h3>Neues Team</h3>
    <div id="admin-reader"></div>
    <form method="post">
        <input type="text" id="admin_id" name="cid" placeholder="QR scannen oder händische ID">
        <input type="text" name="tname" placeholder="Teamname" required>
        <button name="add_p" class="btn-primary">Anlegen</button>
    </form>
</div>
<script>
    new Html5QrcodeScanner("admin-reader", { fps: 10, qrbox: 150 }).render(t => document.getElementById('admin_id').value = t);
</script>
<table>
    <?php
    $res = $db->query("SELECT r.*, p.teamname FROM results r JOIN participants p ON r.participant_id = p.custom_id OR r.participant_id = p.id");
    while($r = $res->fetchArray()) {
        echo "<tr><td>{$r['teamname']}</td><td><a href='index.php?module=admin&del_res={$r['id']}'>Löschen</a></td></tr>";
    }
    ?>
</table>
