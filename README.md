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

## Beispielskripte

Im Verzeichnis [`bin/`](./bin/) liegen Skripte und Einstiegspunkte für Beispiele. Einige davon enthalten fest im Quelltext konfigurierte Verbindungsdaten und Pfade. Vor einer Verwendung in einer eigenen Umgebung müssen diese Werte geprüft und angepasst werden. Zugangsdaten sollten nicht in die Versionsverwaltung eingecheckt werden.

Die Klassen in `src/Console/` enthalten Symfony-Console-Befehle; sie sind jedoch nicht als vorkonfiguriertes, eigenständiges CLI-Programm gebündelt. Für den produktiven Einsatz muss eine Anwendung die Befehle mit den gewünschten Verbindungen und Treibern registrieren.

## Tests

Unit- und Integrationstests sind mit PHPUnit eingerichtet:

```bash
vendor/bin/phpunit
```

Die Integrationstests für MariaDB benötigen eine erreichbare Testdatenbank. [`docker-compose.yml`](./docker-compose.yml) enthält dafür einen MariaDB-Testdienst, der Port `3307` des Hosts auf Port `3306` im Container weiterleitet.

## Lizenz

Dieses Projekt steht unter der [MIT-Lizenz](./LICENSE).
