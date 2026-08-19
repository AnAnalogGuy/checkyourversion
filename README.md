# CheckYourVersion

Eine Web-Anwendung zur Überprüfung von Windows-Software auf Sicherheitslücken und EOL-Status.

## Features

- **Versionsprüfung:** Überprüft die aktuelle Version einer Software
- **EOL-Status:** Zeigt an, ob die Software End-of-Life erreicht hat
- **CVE-Datenbank:** Sucht bekannte Sicherheitslücken
- **Handlungsempfehlung:** Gibt eine priorisierte Empfehlung basierend auf Risiko und EOL-Status

## Tech-Stack

- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Backend:** PHP (LAMP Stack)
- **Datenbank:** MySQL (Live-Abfragen, kein Caching)
- **APIs:**
  - endoflife.date (Versionsinformation & EOL)
  - CVE.org / NVD (Sicherheitslücken)

## Projektstruktur

```
checkyourversion/
├── index.php              (Haupteinstiegspunkt / Frontend)
├── api/
│   └── check.php          (Backend-API Endpoint)
├── css/
│   └── style.css
├── js/
│   └── script.js
├── includes/
│   ├── cve_fetcher.php    (CVE-API Integration)
│   ├── version_checker.php (Endoflife.date Integration)
│   ├── recommendations.php (Empfehlungslogik)
│   └── config.php         (Konfiguration)
└── README.md
```

## Installation

1. Repository klonen
2. In den Projektordner navigieren
3. Abhängigkeiten prüfen (PHP 7.4+, cURL für API-Calls)
4. index.php im Browser öffnen

## Verwendung

1. Software-Name eingeben
2. Hersteller angeben
3. Versionsnummer eingeben
4. "Überprüfen" klicken
5. Ergebnisse inkl. Handlungsempfehlung erhalten