<?php
if (isset($_POST['qr_token'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE login_token = :t");
    $stmt->bindValue(':t', $_POST['qr_token']);
    $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        setcookie("remember_me", $_POST['qr_token'], time() + (86400 * 30), "/");
        exit("success");
    } exit("invalid");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :u");
    $stmt->bindValue(':u', $_POST['username']);
    $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        header("Location: index.php"); exit;
    } else { $err = "Login fehlgeschlagen!"; }
}
?>

<div class="card" style="max-width:400px; margin: 50px auto;">
    <h2 style="text-align:center;">Beerrace Login</h2>
    <?php if(isset($err)) echo "<p style='color:red;'>$err</p>"; ?>
    <form method="post">
        <input type="text" name="username" placeholder="Benutzername" required>
        <input type="password" name="password" placeholder="Passwort" required>
        <button type="submit" class="btn-primary" style="width:100%;">Anmelden</button>
    </form>
    <hr>
    <button type="button" onclick="startLoginScan()" class="btn-success">📲 QR-Code Login</button>
    <div id="login-reader" style="display:none; margin-top:10px;"></div>
</div>

<script>
function startLoginScan() {
    const r = document.getElementById('login-reader'); r.style.display='block';
    const s = new Html5QrcodeScanner("login-reader", {fps:10, qrbox:200});
    s.render(t => {
        let fd = new FormData(); fd.append('qr_token', t);
        fetch('index.php?module=login', {method:'POST', body:fd}).then(res=>res.text()).then(res=>{
            if(res==='success') window.location.href='index.php'; else alert('Ungültig!');
        });
    });
}
</script>
