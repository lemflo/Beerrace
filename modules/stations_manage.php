<?php
if (isset($_POST['add'])) { $stmt=$db->prepare("INSERT INTO stations (name,type) VALUES (:n,:t)"); $stmt->bindValue(':n',$_POST['n']); $stmt->bindValue(':t',$_POST['t']); $stmt->execute(); }
if (isset($_GET['del'])) $db->exec("DELETE FROM stations WHERE id=".(int)$_GET['del']);
?>
<h2>Stationen</h2><form method="post" class="card"><input type="text" name="n" placeholder="Name" required>
<select name="t"><option value="points">Punkte</option><option value="time">Parcours (Min:Sek)</option><option value="start_time">Startzeit (Uhrzeit)</option><option value="end_time">Zielzeit (Uhrzeit)</option></select>
<button name="add" class="btn-primary">Anlegen</button></form>
<table class="card"><?php $res=$db->query("SELECT * FROM stations"); while($s=$res->fetchArray()) echo "<tr><td>{$s['name']} ({$s['type']})</td><td><a href='index.php?module=stations_manage&del={$s['id']}' style='color:red;'>Löschen</a></td></tr>"; ?></table>
