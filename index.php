<?php
session_start(); include 'includes/db.php';
$module = $_GET['module'] ?? 'dashboard';
if (!isset($_SESSION['user_id'])) $module = 'login';
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Beerrace Pro</title><link rel="stylesheet" href="assets/style.css"><script src="https://unpkg.com/html5-qrcode"></script></head>
<body><header><div class="logo">🍻 Beerrace</div><?php if(isset($_SESSION['user_id'])): ?>
<nav><a href="index.php">Home</a> <a href="index.php?module=station">Erfassung</a> <a href="index.php?module=stations_manage">Stationen</a> <a href="index.php?module=participants_manage">Teams</a> <a href="index.php?module=display">Live</a> <a href="modules/logout.php">Logout</a></nav>
<?php endif; ?></header><main class="container"><?php include "modules/$module.php"; ?></main></body></html>
