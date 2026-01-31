<?php
session_start();
include 'includes/db.php';

$module = $_GET['module'] ?? 'dashboard';

// Weiche für CSV Export (Muss vor HTML kommen)
if ($module == 'results_final' && isset($_GET['export'])) {
    include "modules/results_final.php";
    exit;
}

if (!isset($_SESSION['user_id']) && $module != 'login') {
    $module = 'login';
}

// Rechtekontrolle
$currUser = null;
if (isset($_SESSION['user_id'])) {
    $currUser = $db->query("SELECT * FROM users WHERE id = " . $_SESSION['user_id'])->fetchArray(SQLITE3_ASSOC);
    if ($currUser['is_admin'] != 1 && !in_array($module, ['dashboard', 'logout'])) {
        $allowed = json_decode($currUser['permissions'], true) ?: [];
        if (!in_array($module, $allowed)) die("Zugriff verweigert.");
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beerrace Pro 2026</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body>
    <header>
        <div class="container">
            <strong>🍻 Beerrace Pro</strong>
            <?php if (isset($_SESSION['user_id'])): ?>
            <nav>
                <a href="index.php?module=dashboard">Home</a>
                <?php if ($currUser['is_admin'] == 1 || in_array('station', json_decode($currUser['permissions'], true) ?: [])): ?>
                    <a href="index.php?module=station">Erfassung</a>
                <?php endif; ?>
                <a href="index.php?module=results_final">Rangliste</a>
                <?php if ($currUser['is_admin'] == 1): ?>
                    <a href="index.php?module=participants_manage">Teams</a>
                    <a href="index.php?module=stations_manage">Stationen</a>
                    <a href="index.php?module=users_manage">User</a>
                <?php endif; ?>
                <a href="index.php?module=logout" style="color:#e74c3c;">Logout</a>
            </nav>
            <?php endif; ?>
        </div>
    </header>
    <main class="container">
        <?php include "modules/$module.php"; ?>
    </main>
</body>
</html>
