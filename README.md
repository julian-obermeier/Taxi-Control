# Taxi-Control

Taxi-Control ist eine mandantenfähige Taxi-Dispatch-SaaS-Plattform auf Laravel-Basis.

## Verbindliche Architektur

- Eine gemeinsame MySQL-/MariaDB-Datenbank
- Logische Mandantentrennung über `tenant_id`
- Mandantenpfade direkt auf der Domainwurzel, z. B. `/{tenantSlug}`
- Globaler SaaS-Bereich unter `/superadmin`
- Login unter `/login`
- Shared-Hosting-first, ohne Docker-, Redis- oder dauerhafte Node.js-Abhängigkeit
- Deutsche Benutzeroberfläche
- Maximal fünf Entwicklungsphasen; Phase 5 = Release 1.0

Alte Pfade unter `/taxi-control/...` werden aus Kompatibilitätsgründen auf die neuen Root-URLs weitergeleitet.

## Entwicklungsstatus

- **Phase 1 – Fundament & SaaS-Kern: abgeschlossen**
- **Phase 2 – Disposition & Fahrerbetrieb: in Umsetzung**
- Phase 3 – CRM, Portale & Kommunikation: ausstehend
- Phase 4 – Abrechnung, Personal, Fuhrpark & Spezialmodule: ausstehend
- Phase 5 – Vollendung und Release 1.0: ausstehend

Die strukturierte Phase-1-Abnahme liegt unter `docs/PHASE_1_ACCEPTANCE.md`, die Shared-Hosting-Anleitung unter `docs/DEPLOYMENT.md`.
