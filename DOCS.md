# Dokumentation generieren

## Dokumentation lokal generieren

Die API-Dokumentation wird mit phpDocumentor aus den PHP-Dateien in `src/` erstellt. Die generierten Dateien liegen unter `docs/api/`; dieses Verzeichnis ist von Git ausgeschlossen.

### Voraussetzungen

- PHP 8.3 oder neuer
- Composer
- Installierte Projektabhängigkeiten. Falls `vendor/` noch nicht vorhanden ist, im Projektverzeichnis ausführen:

  ```bash
  composer install
  ```

### HTML-Dokumentation erstellen

Im Wurzelverzeichnis des Projekts ausführen:

```bash
./vendor/bin/phpdoc run --directory=src --target=docs/api
```

phpDocumentor verarbeitet damit die PHP-Dateien unter `src/` und erzeugt die HTML-Dokumentation in `docs/api/`. Zum Ansehen die Datei `docs/api/index.html` im Browser öffnen.

### Nützliche Varianten

Bei Bedarf lässt sich ein vollständiger Neuaufbau erzwingen:

```bash
./vendor/bin/phpdoc run --directory=src --target=docs/api --force
```

Die verfügbaren Optionen zeigt:

```bash
./vendor/bin/phpdoc run --help
```

Änderungen an der API-Dokumentation werden beim nächsten Aufruf des Generierungsbefehls übernommen. Die generierten Dateien und der phpDocumentor-Cache (`.phpdoc/`) sind lokale Build-Ausgaben und werden nicht eingecheckt.
