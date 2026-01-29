const startBtn = document.getElementById('startBtn');
const stopBtn = document.getElementById('stopBtn');
const resultContainer = document.getElementById('resultContainer');
const scanResult = document.getElementById('scanResult');
const statusDisplay = document.getElementById('status');

let html5QrCode;

startBtn.addEventListener('click', () => {
    // Falls noch eine Instanz läuft, diese erst löschen
    if (!html5QrCode) {
        html5QrCode = new Html5Qrcode("qr-reader");
    }

    startBtn.classList.add('hidden');
    stopBtn.classList.remove('hidden');
    resultContainer.classList.add('hidden');
    statusDisplay.textContent = "Kamera wird gestartet...";

    const config = { 
        fps: 10, 
        qrbox: { width: 250, height: 250 } 
    };

    html5QrCode.start(
        { facingMode: "environment" }, 
        config,
        (decodedText) => {
            // Erfolg: Ergebnis anzeigen
            scanResult.textContent = decodedText;
            resultContainer.classList.remove('hidden');
            statusDisplay.textContent = "Scan erfolgreich!";
            
            // Optional: Scanner nach Erfolg stoppen
            // stopScanner(); 
        },
        (errorMessage) => {
            // Kontinuierliches Scannen, Fehler (kein QR im Bild) ignorieren
        }
    ).catch(err => {
        statusDisplay.textContent = "Fehler: " + err;
        stopScanner();
    });
});

stopBtn.addEventListener('click', stopScanner);

function stopScanner() {
    if (html5QrCode) {
        html5QrCode.stop().then(() => {
            startBtn.classList.remove('hidden');
            stopBtn.classList.add('hidden');
            statusDisplay.textContent = "Scanner gestoppt.";
        }).catch(err => console.error("Fehler beim Stoppen:", err));
    }
}
