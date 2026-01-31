<?php
session_start();
include 'includes/db.php';

$module = $_GET['module'] ?? 'dashboard';

// Weiche für CSV Export (Muss vor jeglicher HTML-Ausgabe kommen)
if ($module == 'results_final' && isset($_GET['export'])) {
    include "modules/results_final.php";
    exit;
}

// Login-Check: Wenn nicht eingeloggt, erzwinge Login-Modul
if (!isset($_SESSION['user_id']) && $module != 'login') {
    $module = 'login';
}

$currUser = null;
if (isset($_SESSION['user_id'])) {
    // Aktuelle Nutzerdaten aus DB laden für maximale Sicherheit
    $currUser = $db->query("SELECT * FROM users WHERE id = " . (int)$_SESSION['user_id'])->fetchArray(SQLITE3_ASSOC);
    
    if ($currUser) {
        // Admin-Status in Session synchronisieren
        $_SESSION['is_admin'] = (int)$currUser['is_admin'];
        $isAdmin = ($_SESSION['is_admin'] === 1);

        // RECHTE-CHECK
        // Dashoard und Logout darf jeder eingeloggte User sehen
        if (!$isAdmin && !in_array($module, ['dashboard', 'logout', 'login'])) {
            $allowed = json_decode($currUser['permissions'] ?? '[]', true) ?: [];
            if (!in_array($module, $allowed)) {
                die("
                    <div style='font-family:sans-serif; text-align:center; padding:50px;'>
                        <h1 style='color:#e74c3c;'>⛔ Zugriff verweigert</h1>
                        <p>Du hast keine Berechtigung für das Modul <strong>" . htmlspecialchars($module) . "</strong>.</p>
                        <a href='index.php?module=dashboard' style='color:#2c3e50; font-weight:bold;'>Zurück zum Dashboard</a>
                    </div>
                ");
            }
        }
    } else {
        // Falls User in DB nicht mehr existiert -> Session killen
        session_destroy();
        header("Location: index.php?module=login");
        exit;
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
            <?php if (isset($_SESSION['user_id']) && $currUser): ?>
            <nav>
                <a href="index.php?module=dashboard">Home</a>
                <?php 
                $userPerms = json_decode($currUser['permissions'] ?? '[]', true) ?: [];
                $isAdmin = ($currUser['is_admin'] == 1);
                
                if ($isAdmin || in_array('station', $userPerms)) echo '<a href="index.php?module=station">Erfassung</a>';
                ?>
                <a href="index.php?module=results_final">Rangliste</a>
                <?php if ($isAdmin): ?>
                    <a href="index.php?module=participants_manage">Teams</a>
                    <a href="index.php?module=stations_manage">Stationen</a>
                    <a href="index.php?module=users_manage" style="color:var(--accent);">User</a>
                <?php endif; ?>
                <a href="index.php?module=logout" style="color:#e74c3c;">Logout</a>
            </nav>
            <?php endif; ?>
        </div>
    </header>

    <main class="container">
        <?php 
        $target = "modules/$module.php";
        if (file_exists($target)) {
            include $target;
        } else {
            echo "<div class='card'><h2>Fehler</h2><p>Modul '$module' nicht gefunden.</p></div>";
        }
        ?>
    </main>
</body>
</html>
