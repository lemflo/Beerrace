<?php
// Zugriffsschutz: Nur Admins dürfen die Benutzerverwaltung sehen
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
    die("Zugriff verweigert. Dieser Bereich ist Administratoren vorbehalten.");
}

// 1. Verfügbare Module automatisch aus dem Ordner auslesen
$files = array_diff(scandir('modules'), array('..', '.', 'logout.php', 'login.php', 'dashboard.php'));
$available_modules = array_map(function($f) { return str_replace('.php', '', $f); }, $files);

// 2. Logik: Benutzer speichern oder aktualisieren
if (isset($_POST['save_user'])) {
    $uid = $_POST['user_id'] ?? null;
    $uname = $_POST['username'];
    $name = $_POST['name'];
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;
    $perms = json_encode($_POST['perms'] ?? []);

    if (!empty($uid)) {
        // Update bestehender User
        $sql = "UPDATE users SET username=:u, name=:n, is_admin=:a, permissions=:p";
        if (!empty($_POST['password'])) {
            $sql .= ", password=:pass";
        }
        $sql .= " WHERE id=:id";
        
        $stmt = $db->prepare($sql);
        if (!empty($_POST['password'])) $stmt->bindValue(':pass', password_hash($_POST['password'], PASSWORD_DEFAULT));
        $stmt->bindValue(':id', $uid);
    } else {
        // Neuen User anlegen
        $stmt = $db->prepare("INSERT INTO users (username, name, is_admin, permissions, password) VALUES (:u, :n, :a, :p, :pass)");
        $stmt->bindValue(':pass', password_hash($_POST['password'], PASSWORD_DEFAULT));
    }
    $stmt->bindValue(':u', $uname);
    $stmt->bindValue(':n', $name);
    $stmt->bindValue(':a', $is_admin);
    $stmt->bindValue(':p', $perms);
    $stmt->execute();
    echo "<div class='success'>Benutzerdaten wurden erfolgreich gespeichert!</div>";
}

// 3. Logik: Benutzer löschen
if (isset($_GET['del_user'])) {
    $del_id = (int)$_GET['del_user'];
    if ($del_id !== (int)$_SESSION['user_id']) { // Selbstlöschung verhindern
        $db->exec("DELETE FROM users WHERE id = $del_id");
        echo "<div class='success' style='background:#f8d7da; color:#721c24;'>Benutzer wurde entfernt.</div>";
    }
}

$users = $db->query("SELECT * FROM users ORDER BY name ASC");
?>

<h2>👥 Benutzer- & Rechteverwaltung</h2>

<section class="card" style="border-top: 5px solid var(--primary);">
    <h3 id="form-title">Benutzer anlegen / bearbeiten</h3>
    <form method="post" id="userForm">
        <input type="hidden" name="user_id" id="edit_user_id">
        
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
            <div>
                <label>Login-Name:</label>
                <input type="text" name="username" id="edit_username" required placeholder="z.B. station_bier">
            </div>
            <div>
                <label>Anzeigename (für Home-Seite):</label>
                <input type="text" name="name" id="edit_name" required placeholder="z.B. Station Dosenwerfen">
            </div>
            <div>
                <label>Passwort:</label>
                <input type="password" name="password" id="edit_password" placeholder="Passwort eingeben">
                <small id="pw-hint" style="display:none; color:#666;">Leer lassen, um Passwort beizubehalten.</small>
            </div>
            <div style="display:flex; align-items:center; padding-top:20px;">
                <label style="display:flex; align-items:center; gap:10px; cursor:pointer;">
                    <input type="checkbox" name="is_admin" id="edit_is_admin" style="width:auto;" onchange="toggleAdmin(this.checked)">
                    <strong>Administrator (Vollzugriff)</strong>
                </label>
            </div>
        </div>

        <div id="perms_container" style="background:#f9f9f9; padding:15px; border-radius:8px; border:1px solid #ddd;">
            <label><strong>Modul-Berechtigungen:</strong></label>
            <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap:10px; margin-top:10px;">
                <?php foreach($available_modules as $mod): ?>
                <label style="font-weight:normal; display:flex; align-items:center; gap:8px; background:white; padding:5px 10px; border-radius:4px; border:1px solid #eee;">
                    <input type="checkbox" name="perms[]" value="<?= $mod ?>" class="perm-check" style="width:auto;"> 
                    <?= ucfirst(str_replace('_', ' ', $mod)) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="margin-top:20px; display:flex; gap:10px;">
            <button type="submit" name="save_user" class="btn-success" style="flex:2;">💾 Benutzer speichern</button>
            <button type="button" onclick="resetUserForm()" class="btn-primary" style="flex:1; background:#7f8c8d;">Abbrechen / Neu</button>
        </div>
    </form>
</section>

<section class="card">
    <h3>Aktive Benutzer</h3>
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Login</th>
                <th>Rolle</th>
                <th>Aktion</th>
            </tr>
        </thead>
        <tbody>
            <?php while($u = $users->fetchArray(SQLITE3_ASSOC)): ?>
            <tr>
                <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= $u['is_admin'] ? '<span style="color:var(--accent)">⭐ Admin</span>' : 'Helfer' ?></td>
                <td>
                    <button type="button" class="btn-primary" style="padding:5px 10px; font-size:0.75rem;" onclick='fillEditForm(<?= json_encode($u) ?>)'>Edit</button>
                    <?php if($u['id'] != $_SESSION['user_id']): ?>
                        <a href="index.php?module=users_manage&del_user=<?= $u['id'] ?>" class="btn-danger" style="padding:5px 10px; font-size:0.75rem; text-decoration:none;" onclick="return confirm('Benutzer wirklich löschen?')">Löschen</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</section>

<script>
// Schaltet die Checkboxen stumm, wenn Admin ausgewählt ist (da Admins eh alles dürfen)
function toggleAdmin(isAdmin) {
    const checks = document.querySelectorAll('.perm-check');
    checks.forEach(c => {
        c.disabled = isAdmin;
        if(isAdmin) c.checked = true;
    });
}

// Füllt das Formular mit den Daten eines existierenden Users
function fillEditForm(user) {
    document.getElementById('form-title').innerText = "Benutzer bearbeiten: " + user.name;
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_name').value = user.name;
    document.getElementById('edit_password').placeholder = "Neues Passwort vergeben...";
    document.getElementById('pw-hint').style.display = 'block';
    document.getElementById('edit_is_admin').checked = (user.is_admin == 1);
    
    // Rechte setzen
    const perms = JSON.parse(user.permissions || '[]');
    const checks = document.querySelectorAll('.perm-check');
    checks.forEach(c => {
        c.checked = (user.is_admin == 1 || perms.includes(c.value));
    });
    
    toggleAdmin(user.is_admin == 1);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Setzt das Formular auf "Neu anlegen" zurück
function resetUserForm() {
    document.getElementById('form-title').innerText = "Benutzer anlegen / bearbeiten";
    document.getElementById('userForm').reset();
    document.getElementById('edit_user_id').value = '';
    document.getElementById('edit_password').placeholder = "Passwort eingeben";
    document.getElementById('pw-hint').style.display = 'none';
    toggleAdmin(false);
}
</script>
