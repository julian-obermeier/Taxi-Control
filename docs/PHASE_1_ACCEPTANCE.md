# Taxi-Control – Abnahme Phase 1

## Status

**Phase 1 – Fundament & SaaS-Kern: abgeschlossen.**

Abnahmestand: 16.09.2026

Die Abnahme basiert auf dem verbindlichen Taxi-Control-Masterprompt und dem CI-Lauf für den Phase-1-Branch. Die Anwendung befindet sich nach Phase 1 ausdrücklich noch nicht im Release-1.0-Status; operative Taxi-Dispatch-Funktionen folgen gemäß Masterprompt in Phase 2 bis Phase 5.

## 1. Umgesetzter Funktionsumfang

- Laravel-Grundstruktur für PHP-Shared-Hosting
- eine gemeinsame MySQL-/MariaDB-Datenbank
- zentraler Mandantenkontext über `TenantContext`
- fail-closed Mandantenscope für tenantgebundene Modelle
- Schutz vor nachträglichem Wechsel einer `tenant_id`
- Mandantenslugs und Pfadrouting direkt über `/{tenant}`
- separater Superadmin-Bereich `/superadmin`
- Login unter `/login`
- reservierte Systempfade können nicht als Mandanten-Slug vergeben werden
- Kompatibilitätsweiterleitungen von alten `/taxi-control/...`-GET-URLs
- Login und TOTP-2FA
- Superadministrator, Mandantenmitgliedschaften und serverseitige Zugriffskontrollen
- mehrere Rollen pro Benutzer
- granulare Rechte und direkte Benutzer-Overrides mit `deny`-Priorität
- Mandanten-CRUD und Standardrollen-Provisionierung
- mandantenbezogene Benutzer- und Rollenverwaltung
- SaaS-Pakete und Feature-Flags
- mandantenbezogene Feature-Overrides
- Subscription-/Billing-Grundlage
- Onboarding und Go-Live-Prüfung
- Mandantenbranding
- globale Systemeinstellungen und Mandanteneinstellungen
- Audit-Grundlage
- Datenschutz-/Betroffenenanfragen-Grundlage
- versionierte REST-API-Grundlage unter `/api/v1`
- API-Schlüssel mit Scopes, Ablaufdatum und Mandantenbindung
- Webhook-Verwaltung und Testversand mit SSRF-Schutz
- Webinstaller ohne Composer/SSH auf dem Zielserver
- webbasiertes Update-Center mit Manifest, SHA-256-Prüfung und Pfadschutz
- Shared-Hosting-Deploymentworkflow mit mitgelieferten PHP-Abhängigkeiten
- responsive deutsche Grundoberfläche mit Dark-Mode-Unterstützung

## 2. Zentrale Dateien und Bereiche

Die Phase verteilt sich insbesondere auf:

- `app/Tenancy/`
- `app/Models/Concerns/BelongsToTenant.php`
- `app/Models/Scopes/TenantScope.php`
- `app/Http/Middleware/`
- `app/Http/Controllers/Auth/`
- `app/Http/Controllers/Superadmin/`
- `app/Http/Controllers/Tenant/`
- `app/Services/`
- `database/migrations/`
- `database/seeders/`
- `routes/web.php`
- `routes/superadmin.php`
- `routes/tenant.php`
- `routes/api.php`
- `resources/views/`
- `.github/workflows/ci.yml`
- `.github/workflows/build-deployment.yml`
- `docs/DEPLOYMENT.md`

## 3. Tabellen und Migrationen

Phase 1 enthält unter anderem:

- `users`
- `tenants`
- `tenant_user`
- `saas_packages`
- `features`
- `feature_saas_package`
- `subscriptions`
- `tenant_feature_overrides`
- `tenant_settings`
- `system_settings`
- `roles`
- `permissions`
- `role_permission`
- `role_user`
- `user_permission_overrides`
- `audit_logs`
- `api_clients`
- `webhooks`
- `webhook_deliveries`
- `privacy_consents`
- `data_subject_requests`

Die Migrationen sind sowohl mit SQLite in der CI als auch mit MySQL 8.4 erfolgreich durchgelaufen.

## 4. Durchgeführte Prüfungen

Der abschließende CI-Lauf wurde erfolgreich abgeschlossen für:

- PHP 8.2
- PHP 8.3
- MySQL 8.4
- Composer-Abhängigkeitsinstallation
- vollständige Neu-Migration und Seeder
- Laravel-Routenaufbau
- Blade-Kompilierung
- Feature-Test-Suite

Automatisiert geprüft werden insbesondere:

- fail-closed Tenant-Isolation
- Schutz vor Änderung von `tenant_id`
- Cross-Tenant-Zugriff über manipulierten Mandantenslug
- Membership-Prüfung
- serverseitige Route-Berechtigungen
- Superadmin-Abgrenzung
- TOTP-2FA-Zugriffsfluss
- Rollenrechte und direkte Deny-Overrides
- Feature-Flag-Prioritäten
- Subscription-Lifecycle
- SSRF-Schutz für ausgehende URLs
- API-Key-Authentifizierung
- API-Scope-Prüfung
- API-Sperre für deaktivierte Mandanten
- Domain-Root-URL-Erzeugung und Legacy-Redirects

## 5. Buttons und Bedienpfade

Für Phase 1 existieren keine bewusst funktionslosen Buttons, Dummy-Aktionen, Coming-Soon-Seiten oder als fertig markierten Platzhalter. Die zugehörigen Verwaltungsaktionen sind über echte Controller-Routen und serverseitige Validierung angebunden.

Die vollständige End-to-End-Klickprüfung sämtlicher zukünftiger Taxi-Betriebsmodule ist kein Phase-1-Gegenstand und wird spätestens in Phase 5 verbindlich durchgeführt.

## 6. Offene Fehler

Zum Abnahmezeitpunkt bestehen **keine bekannten blockierenden Phase-1-Fehler**.

Ein während der Abnahme gefundener Testfehler im 2FA-HTTP-Test wurde auf eine unvollständige Test-Fixture zurückgeführt: Der Testbenutzer hatte keinen aktiven Mandanten. Der Test wurde auf den realen Ablauf korrigiert und läuft anschließend erfolgreich.

## 7. Bestehender Funktionsumfang

Der bestehende Funktionsumfang blieb während der Phase-1-Erweiterungen intakt. Der abschließende CI-Lauf bestätigt Migrationen, Seeder, Routen, Views sowie die sicherheitskritischen Service- und HTTP-Grenzen.

## 8. Ergebnis

Phase 1 erfüllt damit ihren Zweck als funktionsfähiges Fundament für die nächsten Entwicklungsphasen.

**Nächster verbindlicher Schritt: Phase 2 – Disposition & Fahrerbetrieb.**
