<?php
if (isset($_POST['update_order'])) { foreach ($_POST['order'] as $pos => $id) { $stmt = $db->prepare("UPDATE stations SET sort_order = :so WHERE id = :id"); $stmt->bindValue(':so', $pos); $stmt->bindValue(':id', $id); $stmt->execute(); } exit; }
if (isset($_POST['edit_station'])) { $stmt = $db->prepare("UPDATE stations SET name = :n, type = :t WHERE id = :id"); $stmt->bindValue(':n', $_POST['sname']); $stmt->bindValue(':t', $_POST['stype']); $stmt->bindValue(':id', $_POST['sid']); $stmt->execute(); }
if (isset($_POST['add_station'])) { $next = $db->querySingle("SELECT MAX(sort_order) FROM stations") + 1; $stmt = $db->prepare("INSERT INTO stations (name, type, sort_order) VALUES (:n, :t, :so)"); $stmt->bindValue(':n', $_POST['sname']); $stmt->bindValue(':t', $_POST['stype']); $stmt->bindValue(':so', $next); $stmt->execute(); }
if (isset($_GET['del_stat'])) $db->exec("DELETE FROM stations WHERE id = ".(int)$_GET['del_stat']);
?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<h2>Stationen</h2>
<form method="post" class="card"><h3>Neu</h3><div style="display:flex; gap:10px;"><input type="text" name="sname" required placeholder="Name"><select name="stype"><option value="points">Punkte</option><option value="time">Zeit</option><option value="start_time">Start</option><option value="end_time">Ziel</option></select><button name="add_station" class="btn-primary">OK</button></div></form>
<div id="station-list">
<?php $res = $db->query("SELECT * FROM stations ORDER BY sort_order ASC"); while($s = $res->fetchArray()): ?>
    <div class="card" data-id="<?=$s['id']?>" style="display:flex; gap:10px; cursor:move;"><span>☰</span><form method="post" style="display:flex; flex:1; gap:10px;"><input type="hidden" name="sid" value="<?=$s['id']?>"><input type="text" name="sname" value="<?=$s['name']?>"><select name="stype"><option value="points" <?=$s['type']=='points'?'selected':''?>>Punkte</option><option value="time" <?=$s['type']=='time'?'selected':''?>>Zeit</option><option value="start_time" <?=$s['type']=='start_time'?'selected':''?>>Start</option><option value="end_time" <?=$s['type']=='end_time'?'selected':''?>>Ziel</option></select><button name="edit_station" class="btn-success">💾</button></form><a href="index.php?module=stations_manage&del_stat=<?=$s['id']?>" style="color:red;">🗑️</a></div>
<?php endwhile; ?>
</div>
<script>Sortable.create(document.getElementById('station-list'), { animation: 150, onEnd: function () { const order = Array.from(document.getElementById('station-list').children).map(item => item.dataset.id); const fd = new FormData(); fd.append('update_order', '1'); order.forEach((id, i) => fd.append('order[' + i + ']', id)); fetch('index.php?module=stations_manage', { method: 'POST', body: fd }); } });</script>
