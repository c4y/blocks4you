# Contao - Blocks4you

Mehrere Contao Inhaltselemente (Blöcke) auf einmal einfügen. Die Blöcke werden mit YAML Dateien definiert. Alle Inhalte der Inhaltselemente können vordefiniert werden. Es kann ein Vorschaubild hinterlegt werden.

## Installation

`composer require c4y/blocks4you`. 

In der /config/parameters.yaml den Pfad zu den Blöcken angeben:

```yaml
parameters:
  blocks4you.path: 'templates/blocks'
```

## Verwendung

Im einfachsten Fall sieht die YAML so aus, z.B. `/templates/blocks/text.yaml`. 

```yaml
name: "Text Element"
description: "Ein einfaches Text-Element"
preview: "/files/blocks/text.png"

elements:
  - type: text
```

Etwas komplexeres Beispiel mit mehreren Inhaltselemente: `/templates/blocks/advanced-example.yaml`.

```yaml
name: "Erweitertes Beispiel"
description: "Zeigt sowohl einfache als auch komplexe Felder mit Serialisierung"
preview: "files/blocks/advanced-example.jpg"

elements:
  # Beispiel mit einfachen String-Feldern
  - type: text
    fields:
      text: "<p>Dies ist ein einfacher Text mit String-Wert.</p>"
      cssID: ""
  
  # Beispiel mit komplexem headline-Feld (unit + value)
  - type: headline
    fields:
      headline:
        unit: "h2"
        value: "Komplexe Überschrift mit Unit und Value"
      cssID:
        - "custom-id"
        - "custom-class another-class"
  
  # Beispiel mit Bild und komplexen Feldern
  - type: image
    fields:
      singleSRC: "files/example-image.jpg"
      alt: "Beispielbild"
      size:
        - "400"
        - "300"
        - "crop"
      imagemargin:
        top: "10"
        right: "15"
        bottom: "10"
        left: "15"
        unit: "px"
      floating: "left"
      caption: "Dies ist eine Bildunterschrift"
      fullsize: "1"
  
  # Beispiel mit Text und erweiterten Einstellungen
  - type: text
    fields:
      headline:
        unit: "h3"
        value: "Text mit erweiterten Einstellungen"
      text: "<p>Dieser Text hat erweiterte Einstellungen für Abstände und CSS.</p>"
      space:
        top: "20"
        bottom: "30"
        unit: "px"
      cssID:
        - "text-block"
        - "highlight-section custom-spacing"

```

## to do

Sobald ich herausgefunden habe, wie ich ein oder mehrere Inhaltselemente exportieren kann, liefere ich das nach.