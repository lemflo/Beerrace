<?php
$db_path = 'data/database.sqlite';
if (!file_exists('data')) {
    mkdir('data', 0777, true);
}

if (isset($_POST['setup'])) {
    // Falls die Datenbank existiert, wird sie gelöscht für einen sauberen Neustart
    if (file_exists($db_path)) unlink($db_path);
    
    $db = new SQLite3($db_path);
    
    // Tabellen erstellen
    $db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, password TEXT, name TEXT, is_admin INTEGER DEFAULT 0, permissions TEXT DEFAULT '[]')");
    $db->exec("CREATE TABLE stations (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, type TEXT DEFAULT 'points', sort_order INTEGER DEFAULT 0)");
    $db->exec("CREATE TABLE participants (id INTEGER PRIMARY KEY AUTOINCREMENT, teamname TEXT, p1 TEXT, p2 TEXT, p3 TEXT, p4 TEXT, custom_id TEXT UNIQUE, global_comment TEXT, time_adjustment INTEGER DEFAULT 0)");
    $db->exec("CREATE TABLE results (id INTEGER PRIMARY KEY AUTOINCREMENT, timestamp DATETIME DEFAULT CURRENT_TIMESTAMP, participant_id TEXT, station_id INTEGER, user_id INTEGER, checkin_at DATETIME, checkout_at DATETIME, points INTEGER DEFAULT 0, penalty_points INTEGER DEFAULT 0, time_seconds INTEGER DEFAULT 0, bottles INTEGER DEFAULT 0, comment TEXT, status TEXT DEFAULT 'active', type TEXT)");
    
    // Initialen Admin-Nutzer anlegen (WICHTIG: is_admin = 1)
    $admin_user = 'admin';
    $admin_pass = password_hash("admin123", PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO users (username, password, name, is_admin, permissions) VALUES (:u, :p, :n, 1, '[]')");
    $stmt->bindValue(':u', $admin_user);
    $stmt->bindValue(':p', $admin_pass);
    $stmt->bindValue(':n', 'System Administrator');
    $stmt->execute();
    
    echo "<div style='padding:20px; background:#d4edda; border:1px solid #c3e6cb; border-radius:5px; font-family:sans-serif;'>
            <h2>✅ Setup erfolgreich!</h2>
            <p>Die Datenbank wurde initialisiert und der Administrator wurde erstellt.</p>
            <p><strong>Login:</strong> admin<br><strong>Passwort:</strong> admin123</p>
            <p><a href='index.php'>Hier geht es zum Login</a></p>
          </div>";
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Beerrace Setup</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f4f7f6; }
        .setup-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; }
        button { background: #e74c3c; color: white; border: none; padding: 15px 25px; border-radius: 5px; cursor: pointer; font-weight: bold; }
        button:hover { background: #c0392b; }
    </style>
</head>
<body>
    <div class="setup-box">
        <h1>Beerrace Pro Setup</h1>
        <p>Klicke auf den Button, um die Datenbank zu erstellen.<br><small style="color:red;">Achtung: Bestehende Daten werden gelöscht!</small></p>
        <form method="post">
            <button name="setup" type="submit">Datenbank jetzt initialisieren</button>
        </form>
    </div>
</body>
</html>
