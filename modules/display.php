<div id="live-container">Lade Daten...</div>

<script>
function refreshLive() {
    fetch('api/live_data.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('live-container').innerHTML = html;
        });
}
// Alle 5 Sekunden aktualisieren
setInterval(refreshLive, 5000);
refreshLive();
</script>
