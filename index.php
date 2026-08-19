<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="CheckYourVersion - Überprüfen Sie Windows-Software auf Sicherheitslücken und EOL-Status">
    <title>CheckYourVersion - Software Security Check</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🔍 CheckYourVersion</h1>
            <p>Überprüfen Sie Windows-Software auf Sicherheitslücken und Support-Status</p>
        </header>
        
        <div class="form-container">
            <form id="checkForm">
                <div class="form-group">
                    <label for="software">Software-Name *</label>
                    <input 
                        type="text" 
                        id="software" 
                        name="software" 
                        placeholder="z.B. Windows 10, Adobe Reader, Firefox" 
                        required
                    >
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="vendor">Hersteller</label>
                        <input 
                            type="text" 
                            id="vendor" 
                            name="vendor" 
                            placeholder="z.B. Microsoft, Adobe, Mozilla"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="version">Versionsnummer *</label>
                        <input 
                            type="text" 
                            id="version" 
                            name="version" 
                            placeholder="z.B. 22H2, 2024.1, 131.0" 
                            required
                        >
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Überprüfen</button>
            </form>
        </div>
        
        <div id="results" class="results-container"></div>
        
        <footer>
            <p>CheckYourVersion © 2024 | Datenquellen: endoflife.date, NVD (CVE.org)</p>
            <p style="margin-top: 10px; font-size: 0.85rem; opacity: 0.8;">
                Die Genauigkeit der Ergebnisse hängt von der Verfügbarkeit der Daten in den externen APIs ab.
            </p>
        </footer>
    </div>
    
    <script src="js/script.js"></script>
</body>
</html>
