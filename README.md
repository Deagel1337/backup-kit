# Backup-Kit

Backup-Kit ist eine PHP-Bibliothek zum Erstellen und Wiederherstellen von Datenbank-Dumps und Dateiarchiven. Sie bietet austauschbare Treiber und schrittweise Abläufe für Backups und Restores.

## Funktionen

- Datenbank-Backups und -Restores mit MariaDB, PostgreSQL und SQLite
- Archiv erstellen, auflisten und wiederherstellen mit Borg
- Lokale Archive im TAR-Format
- Validierung von Dumps und Archiven sowie Fortschritts- und Prozessausgabe

Eine Übersicht der Schichten, Abläufe und Erweiterungspunkte steht in der [Architekturdokumentation](./ARCHITECTURE.md).

## Voraussetzungen

- PHP 8.3 oder neuer
- [Composer](https://getcomposer.org/)
- Je nach verwendetem Treiber die passenden Programme:
  - MariaDB: `mariadb` und `mariadb-dump`
  - PostgreSQL: `psql` und `pg_dump`
  - SQLite: `sqlite3`
  - Borg: `borg` sowie bei Remote-Repositories `ssh`
    - TAR-Archive: `tar`

## Installation

Im Projektverzeichnis:

```bash
composer install
```

Als Composer-Abhängigkeit:

```bash
composer require deagel1337/backup-kit
```

## Verwendung

Die Bibliothek wird über ihre Treiber und Anwendungsklassen eingebunden. Die Verbindungsdaten werden beim Erstellen eines `DatabaseConnection`-Objekts angegeben.

### MariaDB-Dump erstellen

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Deagel1337\Backup\Kit\Application\Backup\BackupMariaDbApplication;
use Deagel1337\Backup\Kit\DatabaseBackup\Driver\MariaDbBackupDriver;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseConnection;
use Deagel1337\Backup\Kit\Step\Backup\BackupDatabaseStep;
use Deagel1337\Backup\Kit\Step\Backup\CheckDiskSpaceStep;

$connection = new DatabaseConnection(
    driver: 'mariadb',
    host: getenv('DB_HOST') ?: '127.0.0.1',
    port: (int) (getenv('DB_PORT') ?: 3306),
    database: getenv('DB_DATABASE') ?: '',
    username: getenv('DB_USERNAME') ?: '',
    password: getenv('DB_PASSWORD') ?: '',
);

$driver = new MariaDbBackupDriver($connection);
$application = BackupMariaDbApplication::create([
    new CheckDiskSpaceStep(),
    new BackupDatabaseStep($driver),
]);

$dump = $application->run(__DIR__ . '/backup.sql');
echo "Dump erstellt: {$dump->path}\n";
```

`PostgresBackupDriver` und `SqliteBackupDriver` implementieren dasselbe `DatabaseBackupDriver`-Interface. Die jeweils benötigten Kommandozeilenprogramme müssen auf dem System verfügbar sein.

### Datenbank und Dateien gemeinsam sichern

Aufbauend auf dem `$connection`-Objekt aus dem vorherigen Beispiel kann `BackupApplication` den Datenbank-Dump zusammen mit weiteren Pfaden archivieren. Sie prüft die Quellen vorab, verifiziert die erwarteten Dateinamen im Archiv und entfernt den temporären Dump standardmäßig. Retention ist optional:

```php
use Deagel1337\Backup\Kit\Application\Backup\BackupApplication;
use Deagel1337\Backup\Kit\Archive\Driver\BorgArchiveDriver;
use Deagel1337\Backup\Kit\Archive\Model\RetentionPolicy;
use Deagel1337\Backup\Kit\Services\ArchiveService;

$databaseDriver = new MariaDbBackupDriver($connection);
$archiveService = new ArchiveService(new BorgArchiveDriver(
    repository: getenv('BORG_REPOSITORY') ?: '',
    passphrase: getenv('BORG_PASSPHRASE') ?: '',
));

$result = (new BackupApplication($databaseDriver, $archiveService))->run(
    dumpPath: __DIR__ . '/backup.sql',
    archiveName: 'backup-' . date('Y-m-d-H-i-s'),
    files: [__DIR__ . '/data'],
    retention: new RetentionPolicy(keepDaily: 7, keepWeekly: 4),
);

echo "Backup erstellt: {$result->archive->path}\n";
```

`removeDump` ist standardmäßig `true`; setze es auf `false`, wenn der Dump erhalten bleiben soll. Der Dump wird bei aktivierter Bereinigung auch nach einem fehlgeschlagenen Ablauf entfernt.

### MariaDB-Dump wiederherstellen

Mit dem `$connection`-Objekt aus dem MariaDB-Beispiel stellt `RestoreMariaDbApplication` Datenbank-Dumps wieder her. Der Rollback-Dump vor dem Restore ist optional, wird bei einem fehlgeschlagenen Datenbank-Restore verwendet und anschließend entfernt:

```php
use Deagel1337\Backup\Kit\Application\Restore\RestoreMariaDbApplication;
use Deagel1337\Backup\Kit\DatabaseBackup\Model\DatabaseDump;
use Deagel1337\Backup\Kit\Step\Restore\CreateDatabaseBackupStep;
use Deagel1337\Backup\Kit\Step\Restore\RestoreDatabaseStep;

$databaseDriver = new MariaDbBackupDriver($connection);
$dump = new DatabaseDump(__DIR__ . '/backup.sql', 'mariadb', 'sql');
$databaseDriver->validateRequirements();
$databaseDriver->validateDump($dump);

$restore = RestoreMariaDbApplication::create($databaseDriver, [
    new CreateDatabaseBackupStep($databaseDriver, __DIR__ . '/before-restore.sql'),
    new RestoreDatabaseStep($databaseDriver),
]);
$restore->run($dump);
```

Die Factory ist MariaDB-spezifisch und verwendet einen Konsolen-Reporter. Sie stellt keine Dateien aus einem Archiv wieder her; Datei-Restore-Steps müssen separat konfiguriert werden.

### Borg-Archiv erstellen

Die Borg-Zugangsdaten und der Repository-Pfad können beispielsweise über Umgebungsvariablen bereitgestellt werden:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Deagel1337\Backup\Kit\Application\Archive\ArchiveApplication;
use Deagel1337\Backup\Kit\Archive\Driver\BorgArchiveDriver;
use Deagel1337\Backup\Kit\Services\ArchiveService;

$driver = new BorgArchiveDriver(
    repository: getenv('BORG_REPOSITORY') ?: '',
    passphrase: getenv('BORG_PASSPHRASE') ?: '',
    sshKeyPath: getenv('BORG_SSH_KEY') ?: null,
    sshPort: getenv('BORG_SSH_PORT') !== false
        ? (int) getenv('BORG_SSH_PORT')
        : null,
);

$archive = new ArchiveApplication(new ArchiveService($driver));
$created = $archive->run(
    paths: [__DIR__ . '/data'],
    name: 'backup-' . date('Y-m-d-H-i-s'),
);

echo "Archiv erstellt: {$created->path}\n";
```

Das Borg-Repository muss bereits eingerichtet sein. Bei Remote-Repositories benötigt Borg außerdem einen funktionierenden SSH-Zugang.

### Alte Archive aufräumen

`ArchiveService::prune()` behält Archive nach den angegebenen Retention-Regeln und entfernt ältere. Mindestens eine Regel muss angegeben werden:

```php
$service = new ArchiveService($driver);
$service->prune(
    keepLast: 7,
    keepDaily: 7,
    keepWeekly: 4,
    keepMonthly: 12,
    keepYearly: 3,
);
```

- `keepLast` behält die letzten N Archive.
- `keepDaily`, `keepWeekly`, `keepMonthly` und `keepYearly` behalten jeweils das neueste Archiv aus jedem der N jüngsten Kalenderzeiträume dieser Art (einschließlich des aktuellen Zeitraums).
- Regeln werden kombiniert: Ein Archiv bleibt erhalten, wenn es einer der angegebenen Regeln entspricht. `0` behält für die jeweilige Regel keine Archive; negative Werte sind ungültig.

Für Borg werden die Regeln an `borg prune` weitergereicht. Der Tar-Treiber setzt sie anhand der Änderungszeitpunkte der Dateien um und vergleicht Kalenderzeiträume in der lokalen Zeitzone. Seine Archive liegen standardmäßig in `sys_get_temp_dir() . '/backup-kit-tar'`; für einen dauerhaften oder projektspezifischen Speicherort kann das Verzeichnis beim Erstellen des Treibers angegeben werden:

```php
$driver = new TarArchiveDriver(archiveDirectory: __DIR__ . '/backups');
```

Der Tar-Treiber kann Archive ebenfalls erstellen, auflisten und extrahieren:

```php
use Deagel1337\Backup\Kit\Application\Archive\ArchiveApplication;
use Deagel1337\Backup\Kit\Archive\Driver\TarArchiveDriver;
use Deagel1337\Backup\Kit\Services\ArchiveService;

$archives = new ArchiveApplication(new ArchiveService(
    new TarArchiveDriver(archiveDirectory: __DIR__ . '/backups')
));
$archive = $archives->run([__DIR__ . '/data'], 'local-backup.tar.gz');
$entries = iterator_to_array($archives->list($archive));
$restoreDirectory = __DIR__ . '/restored';
if (! is_dir($restoreDirectory)
    && ! mkdir($restoreDirectory, 0700, true)
    && ! is_dir($restoreDirectory)) {
    throw new RuntimeException('Restore-Verzeichnis konnte nicht erstellt werden.');
}
$archives->extract($archive, $restoreDirectory);
```

## Hinweise zu Verifikation und Restore

Die Prüfung eines erzeugten Backups vergleicht erwartete Einträge anhand ihrer Basenames. Sie erkennt fehlende Dateinamen, ist aber keine Prüfung von Dateiinhalten oder Checksummen. Bei der Archiv-Extraktion in ein bereits vorhandenes Ziel können nach einem Fehler Teildateien zurückbleiben. Ein neues Staging-Verzeichnis wird bei einem fehlgeschlagenen Extract entfernt; dessen Inhalt muss die aufrufende Anwendung nach erfolgreicher Prüfung selbst veröffentlichen.

## Beispielskripte

Im Verzeichnis [`bin/`](./bin/) liegen Skripte und Einstiegspunkte für Beispiele. Einige davon enthalten fest im Quelltext konfigurierte Verbindungsdaten und Pfade. Vor einer Verwendung in einer eigenen Umgebung müssen diese Werte geprüft und angepasst werden. Zugangsdaten sollten nicht in die Versionsverwaltung eingecheckt werden.

Die Klassen in `src/Console/` enthalten Symfony-Console-Befehle; sie sind jedoch nicht als vorkonfiguriertes, eigenständiges CLI-Programm gebündelt. Für den produktiven Einsatz muss eine Anwendung die Befehle mit den gewünschten Verbindungen und Treibern registrieren.

Der Borg-Extraktionsbefehl heißt `borg:extract`. Das Zielverzeichnis ist optional und standardmäßig das aktuelle Verzeichnis. Mit `--repository` kann das konfigurierte Borg-Repository für die Archiv-Auswahl überschrieben werden:

```bash
bin/rbr borg:extract ./restored --repository=/path/to/repository
```

Ohne `--repository` wird das Repository des konfigurierten Archivtreibers verwendet. Passphrase und SSH-Zugangsdaten stammen weiterhin aus der Treiberkonfiguration.

## Tests

Unit- und Integrationstests sind mit PHPUnit eingerichtet:

```bash
vendor/bin/phpunit
```

Die Integrationstests für MariaDB benötigen eine erreichbare Testdatenbank. [`docker-compose.yml`](./docker-compose.yml) enthält dafür einen MariaDB-Testdienst, der Port `3307` des Hosts auf Port `3306` im Container weiterleitet.

## Lizenz

Dieses Projekt steht unter der [MIT-Lizenz](./LICENSE).
