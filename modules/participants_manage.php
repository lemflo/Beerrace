<?php
if (isset($_POST['add'])) { $stmt=$db->prepare("INSERT INTO participants (teamname,p1,p2,custom_id) VALUES (:n,:p1,:p2,:c)"); $stmt->bindValue(':n',$_POST['n']); $stmt->bindValue(':p1',$_POST['p1']); $stmt->bindValue(':p2',$_POST['p2']); $stmt->bindValue(':c',$_POST['c']); $stmt->execute(); }
?>
<h2>Teams</h2><form method="post" class="card"><input type="text" name="n" placeholder="Teamname"><input type="text" name="p1" placeholder="P1"><input type="text" name="p2" placeholder="P2"><input type="text" name="c" placeholder="QR-ID"><button name="add" class="btn-primary">Anlegen</button></form>
<table class="card"><?php $res=$db->query("SELECT * FROM participants"); while($p=$res->fetchArray()) echo "<tr><td>{$p['teamname']}</td><td><a href='index.php?module=team_edit&id={$p['id']}'>Bearbeiten</a></td></tr>"; ?></table>
