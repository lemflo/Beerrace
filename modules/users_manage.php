<?php
if (isset($_POST['add_u'])) {
    $hash = password_hash($_POST['upass'], PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(16));
    $stmt = $db->prepare("INSERT INTO users (username, password, name, login_token) VALUES (:u, :p, :n, :t)");
    $stmt->bindValue(':u', $_POST['uname']);
    $stmt->bindValue(':p', $hash);
    $stmt->bindValue(':n', $_POST['realname']);
    $stmt->bindValue(':t', $token);
    $stmt->execute();
}
if (isset($_GET['del_u']) && $_GET['del_u'] != 1) $db->exec("DELETE FROM users WHERE id = ".(int)$_GET['del_u']);
?>

<h2>Benutzer-Verwaltung</h2>
<section class="card">
    <h3>Neuen Helfer anlegen</h3>
    <form method="post">
        <input type="text" name="uname" placeholder="Benutzername (Login)" required>
        <input type="text" name="realname" placeholder="Anzeigename (z.B. Team Station 1)" required>
        <input type="password" name="upass" placeholder="Passwort" required>
        <button type="submit" name="add_u" class="btn-primary">Benutzer anlegen</button>
    </form>
</section>

<section class="card">
    <h3>Aktive Benutzer</h3>
    <table>
        <thead><tr><th>Name</th><th>Login-Token (für QR-Login)</th><th>Aktion</th></tr></thead>
        <?php
        $res = $db->query("SELECT * FROM users");
        while($u = $res->fetchArray()) {
            echo "<tr>
                    <td><strong>".htmlspecialchars($u['name'])."</strong><br><small>(".htmlspecialchars($u['username']).")</small></td>
                    <td><code>".htmlspecialchars($u['login_token'])."</code></td>
                    <td>";
            if($u['id'] != 1) echo "<a href='index.php?module=users_manage&del_u={$u['id']}' class='btn-danger'>Löschen</a>";
            echo "</td></tr>";
        }
        ?>
    </table>
</section>
