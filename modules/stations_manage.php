<?php
if (isset($_POST['add_station'])) {
    $stmt = $db->prepare("INSERT INTO stations (name) VALUES (:n)");
    $stmt->bindValue(':n', $_POST['sname']);
    $stmt->execute();
}
if (isset($_GET['del_stat'])) $db->exec("DELETE FROM stations WHERE id = ".(int)$_GET['del_stat']);
if (isset($_GET['del_res'])) $db->exec("DELETE FROM results WHERE id = ".(int)$_GET['del_res']);
?>

<h2>Stations-Verwaltung</h2>
<section class="card">
    <form method="post">
        <input type="text" name="sname" placeholder="Stationsname (z.B. Station 1 / Ziel)" required>
        <button type="submit" name="add_station" class="btn-primary">Station anlegen</button>
    </form>
</section>

<div style="display:grid; grid-template-columns: 1fr; gap:20px;">
    <section class="card">
        <h3>Aktive Stationen</h3>
        <table>
            <?php
            $res = $db->query("SELECT * FROM stations");
            while($s = $res->fetchArray()) {
                echo "<tr><td>".htmlspecialchars($s['name'])."</td><td><a href='index.php?module=stations_manage&del_stat={$s['id']}' class='btn-danger'>Löschen</a></td></tr>";
            }
            ?>
        </table>
    </section>

    <section class="card">
        <h3>Alle Ergebnisse (Korrektur)</h3>
        <div style="overflow-x:auto;">
            <table>
                <thead><tr><th>Team</th><th>Station</th><th>Bier</th><th>Pkt</th><th>Strafen</th><th>Aktion</th></tr></thead>
                <?php
                $res = $db->query("SELECT r.*, p.teamname, s.name as sname FROM results r LEFT JOIN participants p ON (r.participant_id = p.custom_id OR r.participant_id = p.id) LEFT JOIN stations s ON r.station_id = s.id WHERE r.status='done'");
                while($r = $res->fetchArray()) {
                    echo "<tr>
                            <td>".htmlspecialchars($r['teamname'])."</td>
                            <td>".htmlspecialchars($r['sname'])."</td>
                            <td>{$r['bottles']}</td>
                            <td>{$r['points']}</td>
                            <td>{$r['penalty_points']}</td>
                            <td><a href='index.php?module=stations_manage&del_res={$r['id']}' class='btn-danger'>Löschen</a></td>
                          </tr>";
                }
                ?>
            </table>
        </div>
    </section>
</div>
