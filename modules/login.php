<?php
if (isset($_POST['login'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :u");
    $stmt->bindValue(':u', $_POST['username']);
    $res = $stmt->execute();
    $user = $res->fetchArray(SQLITE3_ASSOC);

    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['is_admin'] = (int)$user['is_admin']; // Wichtig für den Schnell-Check
        header("Location: index.php?module=dashboard");
        exit;
    } else {
        $error = "Ungültige Anmeldedaten!";
    }
}
?>

<div style="max-width: 400px; margin: 50px auto;">
    <form method="post" class="card">
        <h2 style="text-align:center;">🔒 Login</h2>
        <?php if(isset($error)): ?>
            <div style="color:var(--danger); text-align:center; margin-bottom:10px; font-weight:bold;"><?= $error ?></div>
        <?php endif; ?>
        
        <label>Benutzername:</label>
        <input type="text" name="username" required autofocus>
        
        <label>Passwort:</label>
        <input type="password" name="password" required>
        
        <button type="submit" name="login" class="btn-primary" style="width:100%; margin-top:15px;">Anmelden</button>
    </form>
</div>
