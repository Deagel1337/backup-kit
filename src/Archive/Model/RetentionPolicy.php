<?php

namespace Deagel1337\Backup\Kit\Archive\Model;

use InvalidArgumentException;

/**
 * Aufbewahrungsregeln für Archive. Die Regeln werden kombiniert: Ein Archiv bleibt erhalten,
 * wenn mindestens eine Regel es auswählt.
 */
final readonly class RetentionPolicy
{
    private const KEYS = [
        'last' => 'keepLast',
        'daily' => 'keepDaily',
        'weekly' => 'keepWeekly',
        'monthly' => 'keepMonthly',
        'yearly' => 'keepYearly',
    ];

    /**
     * @param  int|null  $keepLast  Behält die N neuesten Archive.
     * @param  int|null  $keepDaily  Behält das neueste Archiv der N jüngsten Tage.
     * @param  int|null  $keepWeekly  Behält das neueste Archiv der N jüngsten Wochen.
     * @param  int|null  $keepMonthly  Behält das neueste Archiv der N jüngsten Monate.
     * @param  int|null  $keepYearly  Behält das neueste Archiv der N jüngsten Jahre.
     *
     * @throws InvalidArgumentException Wenn keine Regel gesetzt ist, ein Wert negativ ist
     *                                  oder alle Regeln 0 sind (es würde nichts behalten).
     */
    public function __construct(
        public ?int $keepLast = null,
        public ?int $keepDaily = null,
        public ?int $keepWeekly = null,
        public ?int $keepMonthly = null,
        public ?int $keepYearly = null,
    ) {
        $rules = $this->toArray();

        if ($rules === []) {
            throw new InvalidArgumentException('Es muss mindestens eine Aufbewahrungsregel angegeben werden.');
        }

        foreach ($rules as $count) {
            if ($count < 0) {
                throw new InvalidArgumentException('Die Anzahl der zu behaltenden Archive darf nicht negativ sein.');
            }
        }

        if (max($rules) === 0) {
            throw new InvalidArgumentException('Die Aufbewahrungsregeln würden alle Archive entfernen.');
        }
    }

    /**
     * Erstellt die Regeln aus einem Konfigurations-Array, zum Beispiel aus einer Laravel-Config
     * oder YAML-Datei. Erlaubte Schlüssel: last, daily, weekly, monthly, yearly.
     * Ganzzahlen als String (zum Beispiel aus Umgebungsvariablen) werden akzeptiert.
     *
     * @param  array<string, mixed>  $config
     *
     * @throws InvalidArgumentException Bei unbekannten Schlüsseln oder nicht ganzzahligen Werten.
     */
    public static function fromArray(array $config): self
    {
        $arguments = [];

        foreach ($config as $key => $value) {
            if (! isset(self::KEYS[$key])) {
                throw new InvalidArgumentException(
                    sprintf('Unbekannte Aufbewahrungsregel "%s".', $key)
                );
            }

            if ($value === null) {
                continue;
            }

            $count = filter_var($value, FILTER_VALIDATE_INT);

            if ($count === false || is_bool($value)) {
                throw new InvalidArgumentException(
                    sprintf('Die Aufbewahrungsregel "%s" muss eine ganze Zahl sein.', $key)
                );
            }

            $arguments[self::KEYS[$key]] = $count;
        }

        return new self(...$arguments);
    }

    /**
     * Gibt nur die gesetzten Regeln zurück, benannt wie die Parameter von `ArchiveDriver::prune()`.
     *
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return array_filter([
            'keepLast' => $this->keepLast,
            'keepDaily' => $this->keepDaily,
            'keepWeekly' => $this->keepWeekly,
            'keepMonthly' => $this->keepMonthly,
            'keepYearly' => $this->keepYearly,
        ], static fn (?int $count): bool => $count !== null);
    }
}
