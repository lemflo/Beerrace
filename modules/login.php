<?php
if (isset($_POST['qr_token'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE login_token = :t");
    $stmt->bindValue(':t', $_POST['qr_token']);
    $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        setcookie("remember_me", $_POST['qr_token'], time() + (86400 * 30), "/");
        exit("success");
    }
}
?>
<div class="login-box">
    <h2>Login via QR oder Passwort</h2>
    <div id="login-qr-reader"></div>
    <form method="post" style="margin-top:20px;">
        <input type="text" name="username" placeholder="Benutzername">
        <input type="password" name="password" placeholder="Passwort">
        <button type="submit" class="btn-primary">Login</button>
    </form>
</div>
<script>
    function onScanSuccess(token) {
        let fd = new FormData(); fd.append('qr_token', token);
        fetch('index.php?module=login', {method:'POST', body:fd})
        .then(res => res.text()).then(t => { if(t==='success') window.location.reload(); });
    }
    new Html5QrcodeScanner("login-qr-reader", { fps: 10, qrbox: 200 }).render(onScanSuccess);
</script>
