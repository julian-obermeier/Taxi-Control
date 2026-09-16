# Taxi-Control

Taxi-Control ist eine mandantenfähige Taxi-Dispatch-SaaS-Plattform auf Laravel-Basis.

## Verbindliche Architektur

- Eine gemeinsame MySQL-/MariaDB-Datenbank
- Logische Mandantentrennung über `tenant_id`
- Mandantenpfade unter `/taxi-control/{tenantSlug}`
- Globaler SaaS-Bereich unter `/taxi-control/superadmin`
- Shared-Hosting-first, ohne Docker-, Redis- oder dauerhafte Node.js-Abhängigkeit
- Deutsche Benutzeroberfläche
- Maximal fünf Entwicklungsphasen; Phase 5 = Release 1.0

## Entwicklungsstatus

Phase 1 – Fundament & SaaS-Kern: **in Umsetzung**.

Die detaillierte Architektur- und Sicherheitsdokumentation liegt unter `docs/`.
