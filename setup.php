<?php
$db_path = 'data/database.sqlite';
$db_dir = 'data';
if (!file_exists($db_dir)) mkdir($db_dir, 0777, true);
$show_warning = (file_exists($db_path) && !isset($_POST['confirm_delete']));

if (!$show_warning || (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes')) {
    if (file_exists($db_path)) unlink($db_path);
    $db = new SQLite3($db_path);
    $db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT, password TEXT, name TEXT, login_token TEXT)");
    $db->exec("CREATE TABLE stations (id INTEGER PRIMARY KEY, name TEXT, type TEXT DEFAULT 'points')");
    $db->exec("CREATE TABLE participants (id INTEGER PRIMARY KEY AUTOINCREMENT, teamname TEXT, p1 TEXT, p2 TEXT, custom_id TEXT)");
    $db->exec("CREATE TABLE results (id INTEGER PRIMARY KEY AUTOINCREMENT, timestamp DATETIME DEFAULT CURRENT_TIMESTAMP, participant_id TEXT, station_id INTEGER, user_id INTEGER, checkin_at DATETIME, checkout_at DATETIME, points INTEGER DEFAULT 0, penalty_points INTEGER DEFAULT 0, time_seconds INTEGER DEFAULT 0, bottles INTEGER DEFAULT 0, comment TEXT, status TEXT DEFAULT 'active', type TEXT)");
    $pass = password_hash("admin123", PASSWORD_DEFAULT);
    $db->exec("INSERT INTO users (username, password, name, login_token) VALUES ('admin', '$pass', 'Admin', '".bin2hex(random_bytes(16))."')");
    $success = true;
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><link rel="stylesheet" href="assets/style.css"><title>Setup</title></head>
<body style="display:flex;justify-content:center;align-items:center;height:100vh;">
<div class="card" style="text-align:center;">
    <h1>🍻 Beerrace Setup</h1>
    <?php if ($show_warning): ?>
        <p style="color:red;">Warnung: Datenbank existiert bereits!</p>
        <form method="post"><input type="hidden" name="confirm_delete" value="yes"><button class="btn-primary" style="background:red;">Alles löschen & Neu installieren</button></form>
    <?php elseif ($success): ?>
        <p style="color:green;">Setup bereit! Login: admin / admin123</p><a href="index.php" class="btn-primary">Zum Login</a>
    <?php endif; ?>
</div></body></html>
