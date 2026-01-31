<?php
$user = $currUser;
$allowed = json_decode($user['permissions'] ?? '[]', true) ?: [];
$isAdmin = ($user['is_admin'] == 1);
?>
<div class="welcome-header">
    <h1>Moin, <?= htmlspecialchars($user['name']) ?>!</h1>
    <p>Hier sind deine Anleitungen:</p>
</div>

<div class="dashboard-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px;">
    <?php if ($isAdmin || in_array('station', $allowed)): ?>
    <div class="card">
        <h3>📍 Erfassung</h3>
        <p>Scanne Teams oder gib die ID ein. Der Check-in erfolgt <strong>automatisch</strong>. Erfasse beim Check-out Bier, Strafen und ggf. Zeiten.</p>
    </div>
    <?php endif; ?>

    <?php if ($isAdmin || in_array('results_final', $allowed)): ?>
    <div class="card">
        <h3>🏆 Rangliste</h3>
        <p>Echtzeit-Auswertung. Zeitstrafen und Boni aus der Teamakte werden hier live eingerechnet.</p>
    </div>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
    <div class="card" style="border-left:5px solid #f1c40f;">
        <h3>⚙️ Admin-Info</h3>
        <p>In der <strong>Team-Akte</strong> (über "Teams") kannst du Zeitboni (z.B. -60s) oder Strafen (+60s) und globale Notizen vergeben.</p>
    </div>
    <?php endif; ?>
</div>
