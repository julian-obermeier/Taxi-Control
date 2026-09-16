# Taxi-Control – Shared-Hosting-Deployment

## Voraussetzungen

- PHP 8.2 oder 8.3
- MySQL/MariaDB
- PHP-Erweiterungen: PDO MySQL, OpenSSL, Mbstring, Tokenizer, XML, Ctype, Fileinfo und ZIP
- HTTPS
- beschreibbare Verzeichnisse `storage/`, `bootstrap/cache/` und bei Installation das Projektverzeichnis
- Document Root auf `public/`

## Deployment ohne Composer auf dem Server

Der GitHub-Workflow **Build Shared Hosting Package** erzeugt ein ZIP inklusive `vendor/`. Auf dem Produktivserver wird Composer nicht benötigt.

1. Workflow manuell ausführen oder einen Versions-Tag `v*` pushen.
2. Artefakt `taxi-control-shared-hosting.zip` herunterladen.
3. ZIP im gewünschten Hosting-Verzeichnis entpacken.
4. Domain/Document Root auf `<projekt>/public` zeigen lassen.
5. HTTPS aktivieren.
6. `/taxi-control/install` im Browser öffnen.
7. Datenbank- und Superadmin-Daten eingeben.
8. Installation abschließen und unmittelbar 2FA aktivieren.

## Updates

Vor jedem Update ein vollständiges Hosting-Backup von **Dateien und Datenbank** erstellen. Das Update-Center akzeptiert ausschließlich ZIP-Pakete mit `manifest.json`; jede Datei muss mit SHA-256 im Manifest aufgeführt sein. `.env` und Pfade außerhalb der freigegebenen Anwendungsverzeichnisse sind gesperrt. Nach erfolgreicher Prüfung führt Taxi-Control Migrationen und Cache-Bereinigung aus.

## Cron

Für spätere Queue-/Scheduler-Funktionen einen Cronjob minütlich auf `php artisan schedule:run` konfigurieren, soweit der Hoster CLI-Cronjobs unterstützt. Kerninstallation und normale Updates benötigen kein SSH.
