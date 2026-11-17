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

$driver = new MariaDbBackupDriver($connection, singleTransaction: true);
$application = BackupMariaDbApplication::create([
    new CheckDiskSpaceStep(),
    new BackupDatabaseStep($driver),
]);

$dump = $application->run(__DIR__ . '/backup.sql');
echo "Dump erstellt: {$dump->path}\n";
```

Mit `singleTransaction: true` verwendet `mariadb-dump` die Option `--single-transaction`. Für transaktionale Tabellen (z. B. InnoDB) wird so ein konsistenter Snapshot erstellt, ohne die Tabellen während des Dumps zu sperren. Die Option ist standardmäßig deaktiviert, um das bisherige Verhalten beizubehalten; nicht-transaktionale Tabellen erhalten dadurch keine Snapshot-Garantie.

`PostgresBackupDriver` und `SqliteBackupDriver` implementieren dasselbe `DatabaseBackupDriver`-Interface. Die jeweils benötigten Kommandozeilenprogramme müssen auf dem System verfügbar sein.

### Laravel: Fortschritt loggen und als Events melden

Die Laravel-Reporter sind optional; der Kern der Library benötigt Laravel nicht. In einer Laravel-Anwendung kannst du Log- und Fortschritts-Events kombinieren und den Reporter an die MariaDB-Factory übergeben:

```php
use Deagel1337\Backup\Kit\Application\Backup\BackupMariaDbApplication;
use Deagel1337\Backup\Kit\Reporter\CompositeProgressReporter;
use Deagel1337\Backup\Kit\Reporter\Laravel\EventProgressReporter;
use Deagel1337\Backup\Kit\Reporter\Laravel\LogProgressReporter;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

$reporter = new CompositeProgressReporter([
    new LogProgressReporter(Log::channel('backup')),
    new EventProgressReporter(app(Dispatcher::class)),
]);

$application = BackupMariaDbApplication::create($steps, $reporter);
$dump = $application->run(storage_path('app/backups/database.sql'));
```

Laravel stellt `illuminate/contracts` und `psr/log` in der Regel bereits bereit. Außerhalb einer Laravel-Installation müssen diese optionalen Schnittstellen installiert sein, um die Laravel-Reporter zu verwenden (`composer require illuminate/contracts psr/log`). Derselbe Reporter kann als drittes Argument an `RestoreMariaDbApplication::create($driver, $steps, $reporter)` übergeben werden. Ohne eigenen Reporter behalten die MariaDB-Backup- und Restore-Factories ihre Konsolenausgabe; `BackupApplication` verwendet standardmäßig weiterhin `NullProgressReporter`.

`LogProgressReporter` schreibt strukturierte Step-Meldungen über den übergebenen PSR-Logger. `EventProgressReporter` dispatcht `ProgressRunStarted`, `ProgressStepStarted`, `ProgressStepFinished`, `ProgressStepFailed` und `ProgressRunFinished`; eigene Laravel-Listener können diese Events abonnieren. Fehler-Meldungen enthalten den Exception-Typ, aber nicht den Exception-Text, damit potenziell sensible Verbindungsdetails nicht zusätzlich in Logs oder Events gelangen. Der ursprüngliche Fehler wird weiterhin vom Ablauf weitergeworfen.

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

Die Prüfung eines erzeugten Backups vergleicht erwartete Einträge anhand ihrer Basenames. Sie erkennt fehlende Dateinamen, ist aber keine Prüfung von Dateiinhalten oder Checksummen.

Beim `RestoreService` bestimmt die Anwendung die Reihenfolge der Restore-Steps selbst. Ein möglicher direkter Archiv-Restore ist:

```php
use Deagel1337\Backup\Kit\Services\RestoreService;
use Deagel1337\Backup\Kit\Step\Restore\RestoreArchiveStep;
use Deagel1337\Backup\Kit\Step\Restore\ValidateRestoreContextStep;

$restore = new RestoreService(
    steps: [
        new ValidateRestoreContextStep($databaseDriver, $archiveDriver),
        new RestoreArchiveStep($archiveDriver),
    ],
    runner: $runner,
);
$restore->restore($context);
```

`$context` ist ein `RestoreContext` mit Archiv und Ziel; `$runner` ist ein `StepRunner` mit einem Reporter. Ohne Staging wird direkt ins Ziel extrahiert: Der Archivtreiber bestimmt das Überschreibungsverhalten; nicht im Archiv enthaltene Dateien bleiben bestehen. Bei einem Fehler können teilweise überschriebene oder extrahierte Dateien zurückbleiben. Die Validierung ist ein expliziter Step und muss für das tatsächlich verwendete Ziel vor der Extraktion erfolgen.

Für optionales Staging können stattdessen `CreateStagingFolder`, `RestoreArchiveStep` und `CommitStagingStep` in dieser Reihenfolge eingesetzt werden. Der Commit ersetzt das vorhandene Ziel vollständig, sichert es zunächst durch Umbenennung und versucht es bei einem fehlgeschlagenen Commit wiederherzustellen. Der Austausch ist nicht atomar. Weitere fehleranfällige Schritte sollten vor dem Commit liegen.

Cleanup-Steps werden separat über `cleanupSteps: [new CleanStagingFolder, new CleanRollbackDump]` konfiguriert. Sie laufen in dieser Reihenfolge sowohl nach Erfolg als auch nach Fehlern; ein fehlgeschlagener Cleanup-Step verhindert die folgenden nicht. Ohne Konfiguration findet keine automatische Bereinigung durch den Service statt. Der Reporter meldet die Cleanup-Phase separat. Ein optionaler `rollback`-Handler wird nach einem Fehler vor dem Cleanup aufgerufen, sofern der Datenbank-Restore begonnen hat und das Archiv noch nicht übernommen oder direkt extrahiert wurde. Ohne Handler gibt es keinen automatischen Rollback. Die MariaDB-Factory konfiguriert Rollback und Dump-Cleanup selbst.

## Beispielskripte

Im Verzeichnis [`bin/`](./bin/) liegen Skripte und Einstiegspunkte für Beispiele. Einige davon enthalten fest im Quelltext konfigurierte Verbindungsdaten und Pfade. Vor einer Verwendung in einer eigenen Umgebung müssen diese Werte geprüft und angepasst werden. Zugangsdaten sollten nicht in die Versionsverwaltung eingecheckt werden.

Die Klassen in `src/Console/` enthalten Symfony-Console-Befehle; sie sind jedoch nicht als vorkonfiguriertes, eigenständiges CLI-Programm gebündelt. Für den produktiven Einsatz muss eine Anwendung die Befehle mit den gewünschten Verbindungen und Treibern registrieren.

Der Borg-Extraktionsbefehl heißt `borg:extract`. Das Zielverzeichnis ist optional und standardmäßig das aktuelle Verzeichnis. Mit `--repository` kann das konfigurierte Borg-Repository für die Archiv-Auswahl überschrieben werden:

```bash
bin/rbr borg:extract ./restored --repository=/path/to/repository
```

Ohne `--repository` wird das Repository des konfigurierten Archivtreibers verwendet. Passphrase und SSH-Zugangsdaten stammen weiterhin aus der Treiberkonfiguration.

## Tests

Der Standard-Testbefehl führt zuerst die PHPUnit-Unit-Tests aus und startet für die Integrationstests anschließend selbstständig eine temporäre MariaDB:

```bash
composer test
```

Nur die Unit-Tests lassen sich ohne Docker ausführen:

```bash
vendor/bin/phpunit --testsuite Unit
```

Die Integrationstests können bei Bedarf separat ausgeführt werden:

```bash
composer test:integration
```

Der Befehl startet über Docker Compose einen frischen MariaDB-Testcontainer, wartet auf dessen Bereitschaft, führt die Integrationstests aus und entfernt Container und zugehöriges Volume auch nach einem fehlgeschlagenen Testlauf. Der Host-Port wird dynamisch vergeben, damit kein fester Port belegt sein muss. Voraussetzung sind Docker mit Compose-Unterstützung und die PHP-Extension `pdo_mysql`.

Einzelne Integrationstests lassen sich weiterhin direkt mit PHPUnit ausführen, benötigen dann aber eine erreichbare MariaDB unter `127.0.0.1:3307`. Host, Port und Zugangsdaten können für externe Testdatenbanken über `BACKUP_KIT_TEST_DB_HOST`, `BACKUP_KIT_TEST_DB_PORT`, `BACKUP_KIT_TEST_DB_USERNAME` und `BACKUP_KIT_TEST_DB_PASSWORD` gesetzt werden.

## Lizenz

Dieses Projekt steht unter der [MIT-Lizenz](./LICENSE).
