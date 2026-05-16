# Changelog

Alle wichtigen Änderungen an diesem Projekt werden hier dokumentiert.

---

## [1.0.2] - 2026-05-16

### Fixed
- Entfernung veraltetes Backend-Template Override (`be_main.html5`)
- Bereinigung nicht benötigter Core-Asset-Einbindungen

## [1.0.1] - 2026-05-14

### Fixed
- Pflichtfeldlogik für Feuerwehr.Hierarchien korrigiert
- Pflichtfelder für BFK / AFK / FF und BTF validiert und vereinheitlicht
- Darstellung der Fahrzeugauswahl im Backend unter Contao 5.7 korrigiert
- Backend.Asset.Einbindung für Bundle.CSS modernisiert


## [1.0.0] - 2026-05-06

### Added
- Erstes stabiles Release
- GPL-konforme Veröffentlichung vorbereitet
- Lizenzhinweise und Dokumentation überarbeitet
- Vorbereitung für optionale Support- und Servicefunktionen

### Fixed
- Korrekte Sortierung von überörtlichen Kommanden

---

## [0.9.3] - 2026-03-21

### Added
- Contao 5.7 Kompatibilität

### Fixed
- Entfernte Abhängigkeit von Content-Element
- MultiColumnWizard Bereinigung
- Verhalten von Select-Feldern (Chosen)
- DCA Kompatibilitätsverbesserungen
- Alias- und Headline-Generierung
- Rechteverwaltung überarbeitet

---

## [0.9.2] - 2025-07-01

### Added
- Feld "ua" für Feuerwehren
- Felder "theme", "responsible" und "participant" für Events

### Fixed
- AFK-Filter gibt ID statt Namen zurück
- Doppelte Feldnamen im Feuerwehr-Modul
- Backend-Darstellung der Fahrzeugauswahl verbessert
- Fehler bei ce_ff-resources unter anderer Domain behoben

---

## [0.9.1] - 2025-05-23

### Added
- firefighterCourses
- firefighterBadges
- firefighterAwards

### Fixed
- Korrekte Verarbeitung von Delete-Befehlen in MCW-Feldern
- Korrekte Rechtezuweisung für Mitgliederverwaltung