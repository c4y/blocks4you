# Blocks4you

Blocks4you ergänzt Contao um die Backend-Aktion **Block einfügen**. Ein Block ist eine YAML-Vorlage mit Vorschaubild für ein einzelnes Inhaltselement oder ein Set aus mehreren Inhaltselementen. Beim Einfügen legt das Bundle die definierten `tl_content`-Datensätze im aktuellen Artikel an und befüllt optional DCA-Felder vor.

Typische Anwendungsfälle:

- häufig genutzte Inhaltselemente schneller anlegen
- Varianten eines Inhaltselements vorbereiten
- Start-/Stop-Elemente gemeinsam einfügen
- komplexe Content-Sets mit Vorschau für Redakteurinnen und Redakteure auswählbar machen

## Installation

```bash
composer require c4y/blocks4you
```

Der Standardpfad für Block-Definitionen ist:

```text
/files/theme/blocks
```

Vorschaubilder liegen typischerweise darunter in:

```text
/files/theme/blocks/preview
```

Bei Bedarf kann der Pfad in der Projektkonfiguration überschrieben werden:

```yaml
# config/parameters.yaml
parameters:
  blocks4you.path: '/files/theme/blocks'
```

Nach Änderungen an Bundle-Konfiguration, Routen oder DCA:

```bash
php vendor/bin/contao-console cache:clear
```

## Backend-Nutzung

1. Im Contao-Backend einen Artikel öffnen.
2. In der Inhaltselement-Liste auf **Block einfügen** klicken.
3. Einen Block anhand von Name, Beschreibung und Vorschau auswählen.
4. Der Block wird an der gewählten Einfügeposition als Inhaltselement oder Element-Set angelegt.

Die Aktion ist als globale Operation für `tl_content` registriert und führt zur Backend-Route `/contao/blocks`.

## Block-Dateien

Blocks4you liest alle `.yaml`- und `.yml`-Dateien aus dem konfigurierten Block-Pfad. Ungültige YAML-Dateien werden übersprungen. Die Auswahl wird alphabetisch nach `name` sortiert.

Minimales Beispiel:

```yaml
name: "Text"
description: "Fügt ein leeres Text-Element ein."
preview: "/files/theme/blocks/preview/text.svg"

blocks:
  - text

elements:
  - type: text
```

Beispiel mit mehreren Inhaltselementen:

```yaml
name: "Swiper"
description: "Fügt einen Swiper-Start und Swiper-Stop ein."
preview: "/files/theme/blocks/preview/swiper.svg"

blocks:
  - rsce_swiper_start
  - rsce_swiper_stop

elements:
  - type: rsce_swiper_start
    fields:
      effect: "slide"
      slidesToShow: "1.15"
      slidesToShowGt768: "2"
      slidesToShowGt1280: "3"
      gap: "20"
  - type: rsce_swiper_stop
```

## YAML-Felder

`name`
: Anzeigename in der Block-Auswahl. Wenn kein Name gesetzt ist, wird der Dateiname verwendet.

`description`
: Kurze Beschreibung für die Backend-Auswahl.

`preview`
: Öffentlich erreichbarer Pfad zu einem Vorschaubild, zum Beispiel `/files/theme/blocks/preview/hero.svg`.

`blocks`
: Liste der enthaltenen Elementtypen für die Anzeige in der Auswahl. Diese Liste ist beschreibend; eingefügt wird ausschließlich, was unter `elements` definiert ist.

`elements`
: Liste der anzulegenden Inhaltselemente. Jeder Eintrag braucht mindestens `type`.

`elements.*.fields`
: Optionale Feldwerte, die direkt in `tl_content` gespeichert werden.

## Feldwerte

Einfache Werte werden unverändert gespeichert:

```yaml
elements:
  - type: rsce_info_box
    fields:
      as_card: "1"
      alignment: "center"
```

Array-Werte werden automatisch serialisiert. Das passt zu vielen Contao-Feldern wie `headline`, `cssID`, Größen- oder Abstandsfeldern:

```yaml
elements:
  - type: text
    fields:
      headline:
        unit: "h2"
        value: "Überschrift"
      cssID:
        - ""
        - "highlight-section"
```

Leere Strings und `null` werden als leerer Wert gespeichert.

## Entwicklung

Wichtige Dateien im Bundle:

- `contao/dca/tl_content.php` registriert die globale Backend-Aktion.
- `src/Controller/BlocksController.php` rendert die Auswahl und verarbeitet das Einfügen.
- `src/Service/BlockService.php` liest YAML-Dateien und schreibt die Inhaltselemente.
- `templates/blocks.html.twig` enthält die Backend-Auswahl.
- `public/css/blocks.css` und `public/icons/block-insert.svg` enthalten Backend-Assets.

Sinnvolle Prüfungen nach Änderungen:

```bash
php -l contao/dca/tl_content.php
php -l src/Controller/BlocksController.php
php -l src/Service/BlockService.php
php vendor/bin/contao-console cache:clear
```
