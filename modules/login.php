<?php
// 1. Logik für QR-Code Login (AJAX/Fetch)
if (isset($_POST['qr_token'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE login_token = :t");
    $stmt->bindValue(':t', $_POST['qr_token']);
    $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        setcookie("remember_me", $_POST['qr_token'], time() + (86400 * 30), "/");
        exit("success");
    } else {
        exit("invalid");
    }
}

// 2. Logik für klassischen Login (Benutzername & Passwort)
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $db->prepare("SELECT * FROM users WHERE username = :u");
    $stmt->bindValue(':u', $username);
    $res = $stmt->execute();
    $user = $res->fetchArray(SQLITE3_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Login erfolgreich
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        
        // Optional: Auch hier Cookie setzen, falls gewünscht
        setcookie("remember_me", $user['login_token'], time() + (86400 * 30), "/");
        
        header("Location: index.php");
        exit;
    } else {
        $error = "Benutzername oder Passwort falsch!";
    }
}
?>

<div class="login-box">
    <h2 style="text-align:center;">Beerrace Login</h2>
    
    <?php if($error): ?>
        <div style="background:#f8d7da; color:#721c24; padding:10px; border-radius:5px; margin-bottom:15px;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div id="login-qr-reader" style="width: 100%; border-radius: 8px; overflow: hidden;"></div>
    <p style="text-align:center; font-size:0.9em; color:#666;">Oder mit Zugangsdaten anmelden:</p>

    <form method="post" action="index.php?module=login">
        <label>Benutzername:</label>
        <input type="text" name="username" required placeholder="z.B. admin">
        
        <label>Passwort:</label>
        <input type="password" name="password" required placeholder="Ihr Passwort">
        
        <button type="submit" class="btn-primary" style="margin-top:10px;">Einloggen</button>
    </form>
</div>

<script>
    function onScanSuccess(token) {
        let fd = new FormData(); 
        fd.append('qr_token', token);
        fetch('index.php?module=login', {method:'POST', body:fd})
        .then(res => res.text())
        .then(t => { 
            if(t === 'success') {
                window.location.href = 'index.php';
            } else {
                alert("Ungültiger QR-Login-Token!");
            }
        });
    }
    
    // Scanner nur starten, wenn Element existiert
    if (document.getElementById('login-qr-reader')) {
        const loginScanner = new Html5QrcodeScanner("login-qr-reader", { fps: 10, qrbox: 200 });
        loginScanner.render(onScanSuccess);
    }
</script>
