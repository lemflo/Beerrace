<?php
$db_path = 'data/database.sqlite';
if (!file_exists('data')) mkdir('data', 0777, true);

if (isset($_POST['setup'])) {
    if (file_exists($db_path)) unlink($db_path);
    $db = new SQLite3($db_path);
    $db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, password TEXT, name TEXT, is_admin INTEGER DEFAULT 0, permissions TEXT DEFAULT '[]')");
    $db->exec("CREATE TABLE stations (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, type TEXT DEFAULT 'points', sort_order INTEGER DEFAULT 0)");
    $db->exec("CREATE TABLE participants (id INTEGER PRIMARY KEY AUTOINCREMENT, teamname TEXT, p1 TEXT, p2 TEXT, p3 TEXT, p4 TEXT, custom_id TEXT UNIQUE, global_comment TEXT, time_adjustment INTEGER DEFAULT 0)");
    $db->exec("CREATE TABLE results (id INTEGER PRIMARY KEY AUTOINCREMENT, timestamp DATETIME DEFAULT CURRENT_TIMESTAMP, participant_id TEXT, station_id INTEGER, user_id INTEGER, checkin_at DATETIME, checkout_at DATETIME, points INTEGER DEFAULT 0, penalty_points INTEGER DEFAULT 0, time_seconds INTEGER DEFAULT 0, bottles INTEGER DEFAULT 0, comment TEXT, status TEXT DEFAULT 'active', type TEXT)");
    
    $admin_pass = password_hash("admin123", PASSWORD_DEFAULT);
    $db->exec("INSERT INTO users (username, password, name, is_admin) VALUES ('admin', '$admin_pass', 'Haupt-Admin', 1)");
    echo "Setup erfolgreich! Login: admin / admin123";
}
?>
<form method="post"><button name="setup">Datenbank neu erstellen (LÖSCHT ALLES!)</button></form>
