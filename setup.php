<?php
$db_path = 'data/database.sqlite';
$db_dir = 'data';

if (!file_exists($db_dir)) mkdir($db_dir, 0777, true);

$show_warning = (file_exists($db_path) && !isset($_POST['confirm_delete']));

if (!$show_warning || (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes')) {
    if (file_exists($db_path)) unlink($db_path);
    $db = new SQLite3($db_path);
    $db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT, password TEXT, name TEXT, login_token TEXT)");
    $db->exec("CREATE TABLE stations (id INTEGER PRIMARY KEY, name TEXT)");
    $db->exec("CREATE TABLE participants (id INTEGER PRIMARY KEY AUTOINCREMENT, teamname TEXT, p1 TEXT, p2 TEXT, custom_id TEXT)");
    $db->exec("CREATE TABLE results (
        id INTEGER PRIMARY KEY AUTOINCREMENT, 
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP, 
        participant_id TEXT, 
        station_id INTEGER, 
        user_id INTEGER, 
        checkin_at DATETIME,
        checkout_at DATETIME,
        points INTEGER DEFAULT 0, 
        penalty_points INTEGER DEFAULT 0,
        time_seconds INTEGER DEFAULT 0, 
        bottles INTEGER DEFAULT 0, 
        comment TEXT,
        status TEXT DEFAULT 'active',
        type TEXT)");
    
    $pass = password_hash("admin123", PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(16));
    $db->exec("INSERT INTO users (username, password, name, login_token) VALUES ('admin', '$pass', 'Administrator', '$token')");
    $success = true;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"><title>Beerrace Setup</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body style="display:flex; justify-content:center; align-items:center; height:100vh;">
<div class="card" style="text-align:center;">
    <h1>🍻 Beerrace Setup</h1>
    <?php if ($show_warning): ?>
        <div style="color:red; margin-bottom:20px;"><strong>WARNUNG:</strong> Datenbank vorhanden! Fortfahren löscht alle Daten.</div>
        <form method="post">
            <input type="hidden" name="confirm_delete" value="yes">
            <button type="submit" class="btn-primary" style="background:red;">Ja, alles löschen</button>
            <a href="index.php" class="btn-primary" style="background:gray; text-decoration:none;">Nein, abbrechen</a>
        </form>
    <?php elseif ($success): ?>
        <div style="color:green; margin-bottom:20px;">Setup erfolgreich! Login: admin / admin123</div>
        <a href="index.php?module=login" class="btn-primary" style="text-decoration:none;">Zum Login</a>
    <?php endif; ?>
</div>
</body>
</html>
