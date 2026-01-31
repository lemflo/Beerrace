<?php
$tid = (int)$_GET['id'];
$t = $db->query("SELECT * FROM participants WHERE id=$tid")->fetchArray(SQLITE3_ASSOC);
if (!$t) die("Team nicht gefunden.");

// ... (Update-Logik wie zuvor) ...

?>
<div class="card" style="border-top:5px solid var(--accent);">
    <h3>Stammdaten: <?= htmlspecialchars($t['teamname']) ?></h3>
    <form method="post">
        <button name="update_team" class="btn-primary" style="width:100%;">Speichern</button>
    </form>
</div>

<h3>Stations-Ergebnisse</h3>
<?php 
// JOIN mit der Users-Tabelle für den Namen des Helfers
$res = $db->query("SELECT r.*, s.name as sname, u.name as helper_name 
                   FROM results r 
                   JOIN stations s ON r.station_id=s.id 
                   LEFT JOIN users u ON r.user_id = u.id
                   WHERE (r.participant_id='{$t['custom_id']}' OR r.participant_id='$tid') 
                   ORDER BY r.timestamp DESC");

while($r = $res->fetchArray(SQLITE3_ASSOC)): ?>
<div class="card" style="font-size: 0.9rem;">
    <div style="display:flex; justify-content:space-between; border-bottom:1px solid #eee; margin-bottom:10px; padding-bottom:5px;">
        <strong>📍 <?= htmlspecialchars($r['sname']) ?></strong>
        <span style="color:#666; font-size:0.8rem;">
            Erfasst von: <strong><?= htmlspecialchars($r['helper_name'] ?? 'Unbekannt') ?></strong> 
            am <?= date('d.m. H:i', strtotime($r['checkout_at'] ?? $r['timestamp'])) ?>
        </span>
    </div>
    
    <form method="post" style="display:grid; grid-template-columns: repeat(3, 1fr) auto; gap:10px; align-items:end;">
        <input type="hidden" name="rid" value="<?= $r['id'] ?>">
        <label>Bier:<input type="number" name="b" value="<?= $r['bottles'] ?>"></label>
        <label>Punkte:<input type="number" name="p" value="<?= $r['points'] ?>"></label>
        <label>Strafen:<input type="number" name="pp" value="<?= $r['penalty_points'] ?>"></label>
        <button name="update_res" class="btn-success" style="padding: 10px;">💾</button>
    </form>
</div>
<?php endwhile; ?>
