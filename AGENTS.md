# AGENTS.md

Anleitung fuer Coding-Agents in `tfd/statamic-cloudinary`.

## Projektueberblick

- Dieses Repository ist ein PHP-Paket fuer Statamic/Laravel.
- Paketname: `tfd/statamic-cloudinary`.
- PSR-4-Root-Namespace: `TFD\Cloudinary\`, gemappt auf `src/`.
- Der Hauptcode liegt in `src/`.
- Die Laufzeitkonfiguration liegt in `config/cloudinary.php`.
- Blade-Views liegen in `resources/views/`.
- Es gibt aktuell keine Frontend-Build-Pipeline fuer dieses Addon.
- Testsuite: Pest unter `tests/` (Orchestra Testbench), z. B. `tests/Unit/CloudinaryConverterTest.php`.

## Quellen Fuer Agent-Regeln

- Vor dieser Datei gab es kein Repository-weites `AGENTS.md`.
- Es wurde keine `.cursorrules`-Datei gefunden.
- Es wurden keine Dateien in `.cursor/rules/` gefunden.
- Es wurde keine `.github/copilot-instructions.md` gefunden.
- Falls spaeter solche Dateien hinzukommen, gelten sie als zusaetzliche Anweisungen.

## Umgebung Und Tooling

- Paketmanager: Composer.
- Formatter/Linter: PHP CS Fixer ueber `.php-cs-fixer.php`.
- Dev-Dependencies: u. a. `pestphp/pest`, `orchestra/testbench`, `mockery/mockery`, `phpunit/phpunit`.
- Das Paket zielt auf Statamic `^5.73.11 || ^6.4`.
- Laravel-Helper wie `config()`, `resource_path()` und `collect()` koennen vorausgesetzt werden.

## Setup-Befehle

Abhaengigkeiten installieren:

```bash
composer install
```

Nur Produktions-Abhaengigkeiten installieren:

```bash
composer install --no-dev
```

Composer-Autoload nach Namespace- oder Dateiaenderungen aktualisieren:

```bash
composer dump-autoload
```

## Build-Befehle

- Es gibt keinen dedizierten Build-Schritt fuer dieses Paket.
- `composer install` und `composer dump-autoload` sind die relevanten Setup-/Build-aehnlichen Befehle.
- Fuer eine Paketvalidierung kann Composer selbst verwendet werden:

```bash
composer validate
```

## Lint- Und Formatierungsbefehle

Repository-definierter Lint-Befehl:

```bash
composer lint
```

Das expandiert zu:

```bash
php ./vendor/bin/php-cs-fixer fix -v --config .php-cs-fixer.php
```

Sichere Check-only-Variante vor Aenderungen:

```bash
php ./vendor/bin/php-cs-fixer fix --dry-run --diff -v --config .php-cs-fixer.php
```

## Test-Befehle

Alle Tests (Pest):

```bash
composer test
```

Entspricht:

```bash
./vendor/bin/pest
```

Mit PHPUnit-Filter (Pest nutzt PHPUnit darunter):

```bash
./vendor/bin/pest --filter=CloudinaryConverter
```

Konfiguration: `phpunit.xml` im Repository-Root; `tests/Pest.php` bindet `Tests\TestCase` (Orchestra Testbench) fuer `tests/Unit`.

## Dateien Und Verantwortlichkeiten

- `src/ServiceProvider.php`: Paketregistrierung, Publish-Definitionen, Blade-Komponenten, Laden von Views.
- `src/Converter/CloudinaryConverter.php`: zentrale Transformations- und URL-Generierungslogik.
- `src/Tags/Cloudinary.php`: Statamic-Tag-Implementierung und Glide-Fallback-Verhalten.
- `src/Components/Image.php`: Blade-Komponente als Wrapper um den Converter.
- `src/Interfaces/CloudinaryInterface.php`: kleines gemeinsames Interface.
- `config/cloudinary.php`: Paket-Defaults und Konfigurationsverdrahtung.
- `resources/views/components/image.blade.php`: gerendertes Bild-Markup.

## Grundsaetzlicher Code-Style

Orientiere dich zuerst am bestehenden Stil im Repository und lasse das Ergebnis anschliessend von PHP CS Fixer normalisieren.

- Verwende PSR-4-Namespaces unter `TFD\Cloudinary`.
- Verwende Short-Array-Syntax: `[]`.
- Verwende einfache Anfuehrungszeichen, ausser Interpolation oder Escaping machen doppelte Anfuehrungszeichen klarer.
- Ein Import pro Statement.
- Imports alphabetisch sortieren.
- Ungenutzte Imports entfernen.
- Genau eine Leerzeile nach dem Namespace und nach dem Import-Block.
- Explizite Sichtbarkeit fuer Methoden und Properties.
- Moeglichst ein Klassen-Element pro Statement.
- Dateien mit genau einer abschliessenden Leerzeile beenden.
- Kein schliessendes `?>` in PHP-Dateien.

## Imports

- Framework- und Paketklassen am Dateianfang importieren.
- Import-Aliasse nur verwenden, wenn Namenskonflikte oder Verwechslungen drohen, z. B. `Asset as AssetsAsset`.
- Vollqualifizierte Klassennamen im Inline-Code vermeiden, wenn ein `use`-Statement klarer ist.
- Der Fixer erzwingt keine fuehrenden Import-Slashes und alphabetische Sortierung.

## Formatierung

- Die bestehende Einrueckung mit 4 Leerzeichen beibehalten.
- Klammern und Methodenlayout konsistent zum restlichen Code halten.
- Leerzeilen nur zwischen logischen Bloecken einsetzen, nicht stapeln.
- Einfache Delegationsmethoden kompakt halten.
- Mehrzeilige Arrays verwenden, wenn die Lesbarkeit steigt.
- Trailing Commas in mehrzeiligen Arrays beibehalten.
- String-Konkatenation ohne zusaetzliche Leerzeichen um `.` schreiben.

## Typen Und Signaturen

- Die bestehende Codebasis verwendet nur wenige PHP-Typdeklarationen.
- Beim Bearbeiten vorhandener Dateien die Kompatibilitaet zum umgebenden Code bewahren.
- Keine aggressiven Scalar-Type-Hints oder Return-Types ueber das ganze Paket verteilen, sofern nicht alle betroffenen Verwendungen konsistent angepasst werden.
- Constructor Dependency Injection ist das bevorzugte Muster fuer Services und Komponenten.
- Bei neuen APIs Typen bevorzugen, wenn sie fuer die unterstuetzte PHP-/Laravel-/Statamic-Matrix offensichtlich sicher sind.
- `Collection` bewusst einsetzen, wenn eine Methode bereits in Collection-Begriffen arbeitet.

## Benennungskonventionen

- Klassen verwenden PascalCase.
- Methoden und Properties verwenden camelCase.
- Config-Keys und Array-Keys verwenden snake_case, wenn sie externe Konfiguration oder Cloudinary-Parameter abbilden.
- Begriffe an Statamic- und Cloudinary-Terminologie ausrichten: `asset`, `tag`, `transformations`, `delivery_type`, `fetch_format`.
- Namespace-Ordner sollen den Zweck der Klassen widerspiegeln, z. B. `Tags`, `Components`, `Converter`, `Interfaces`.

## Fehlerbehandlung Und Logging

- Wenn moeglich, graceful Fallbacks statt harter Fehler verwenden, insbesondere wenn an Statamic Glide delegiert werden kann.
- Der bestehende Code faengt `ItemNotFoundException` ab und loggt ueber `Log::error(...)`.
- Fallback-Pfade intakt lassen, wenn Konfiguration fehlt oder Asset-Lookups fehlschlagen.
- Logging fuer operative Probleme verwenden, die das Rendering nicht komplett abbrechen sollen.
- Exceptions nicht stillschweigend schlucken, ausser es gibt einen klaren Grund; wenn Schweigen notwendig ist, Verhalten dokumentieren oder das etablierte Muster beibehalten.

## Paketspezifische Konventionen

- Vor der Generierung von Cloudinary-URLs immer die Konfiguration validieren.
- Das Glide-Fallback in `src/Tags/Cloudinary.php` erhalten, wenn Cloudinary nicht verfuegbar ist.
- Die Uebersetzung von Parametern zentral in `CloudinaryConverter` halten.
- Statamic-Asset-IDs, Asset-URLs und gemappte externe URLs als unterstuetzte Inputs behandeln.
- Publish-Pfade fuer Assets/Views und bestehende Tag-Namen nur aendern, wenn bewusst ein Breaking Change gewollt ist.

## Blade- Und View-Konventionen

- Blade-Komponenten-Ausgabe einfach und vorhersagbar halten.
- Dynamische Werte mit normaler Blade-Escaping-Syntax `{{ }}` ausgeben.
- Vorbereitete View-Daten bevorzugt in PHP uebergeben, statt Transformationslogik in Blade zu verstecken.
- HTML-Attribute explizit halten, wenn sie Ausgabedimensionen oder Accessibility beeinflussen.

## Beim Aendern Von Code

- Die kleinstmoegliche Aenderung machen, die das Problem loest.
- Breite Refactorings vermeiden, ausser die Aufgabe verlangt es.
- Neue Tests unter `tests/` ablegen; `composer test` ausfuehren (siehe Abschnitt Test-Befehle).
- Wenn du Formatierung spuerbar veraenderst, anschliessend PHP CS Fixer ausfuehren.
- Wenn du neue Klassen unter `src/` hinzufuegst, sicherstellen, dass Namespace und Pfad zu PSR-4 passen.
- `README.md` aktualisieren, wenn sich oeffentliches Verhalten oder Konfiguration aendert.

## Worauf Besonders Zu Achten Ist

- Einige aktuelle Dateien haben uneinheitliche Abstaende; beim Bearbeiten fixer-konforme Formatierung bevorzugen.
- `composer lint` veraendert Dateien, weil intern `php-cs-fixer fix` und kein Dry-Run verwendet wird.
- Nach relevanten Aenderungen `composer test` ausfuehren.
- Bei Aenderungen an generierten URLs, Fallback-Semantik oder Config-Key-Namen besonders vorsichtig sein.

## Empfohlener Agent-Workflow

1. Relevante Datei und naheliegende Verwendungen vor dem Editieren lesen.
2. Eine gezielte Aenderung im Stil bestehender Statamic-/Laravel-Muster vornehmen.
3. Je nach Situation `composer lint` oder die Dry-Run-Variante ausfuehren.
4. `composer test` oder gezielt `./vendor/bin/pest --filter=...` ausfuehren.
5. Verhaltensaenderungen, Fallback-Auswirkungen und Konfigurationsfolgen knapp zusammenfassen.
