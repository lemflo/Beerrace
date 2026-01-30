<?php
$db = new SQLite3('data/database.sqlite');
$queries = [
    "CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, username TEXT, password TEXT, name TEXT, login_token TEXT)",
    "CREATE TABLE IF NOT EXISTS stations (id INTEGER PRIMARY KEY, name TEXT)",
    "CREATE TABLE IF NOT EXISTS participants (id INTEGER PRIMARY KEY AUTOINCREMENT, teamname TEXT, p1 TEXT, p2 TEXT, custom_id TEXT)",
    "CREATE TABLE IF NOT EXISTS results (id INTEGER PRIMARY KEY, timestamp DATETIME DEFAULT CURRENT_TIMESTAMP, participant_id TEXT, station_id INTEGER, user_id INTEGER, points INTEGER, time_seconds INTEGER, type TEXT)"
];
foreach ($queries as $q) $db->exec($q);
$pass = password_hash("admin123", PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(16));
$db->exec("INSERT OR IGNORE INTO users (id, username, password, name, login_token) VALUES (1, 'admin', '$pass', 'Administrator', '$token')");
echo "Setup fertig. Admin-Token für QR-Login: $token <br><a href='index.php'>Zum Login</a>";
