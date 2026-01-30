<?php
session_start();
include 'includes/db.php';

// Persistent Login Check (Angemeldet bleiben)
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE login_token = :t");
    $stmt->bindValue(':t', $_COOKIE['remember_me']);
    $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
    }
}

$module = $_GET['module'] ?? 'dashboard';
if (!isset($_SESSION['user_id'])) $module = 'login';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Beerrace Pro</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body>
<header>
    <div class="logo">🍻 Beerrace</div>
    <?php if(isset($_SESSION['user_id'])): ?>
    <nav>
        <a href="index.php">Main</a>
        <a href="index.php?module=station">Station</a>
        <a href="index.php?module=display">Live</a>
        <a href="index.php?module=admin">Admin</a>
        <a href="modules/logout.php">Logout</a>
    </nav>
    <?php endif; ?>
</header>
<main class="container">
    <?php 
    $path = "modules/$module.php";
    if(file_exists($path)) {
        include $path; 
    } else {
        echo "<h1>Hallo, " . htmlspecialchars($_SESSION['name']) . "!</h1>";
        echo "<p>Wähle eine Aktion aus dem Menü oder direkt hier:</p>";
        echo "<div class='grid-menu'>
                <a href='index.php?module=station' class='card'>⏱ Station erfassen</a>
                <a href='index.php?module=display' class='card'>🏆 Live-Ergebnisse</a>
                <a href='index.php?module=admin' class='card'>⚙️ Verwaltung</a>
              </div>";
    }
    ?>
</main>
</body>
</html>
