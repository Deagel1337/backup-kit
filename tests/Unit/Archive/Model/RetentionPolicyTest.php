<?php

namespace Tests\Unit\Archive\Model;

use Deagel1337\Backup\Kit\Archive\Model\RetentionPolicy;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RetentionPolicyTest extends TestCase
{
    public function test_to_array_contains_only_configured_rules(): void
    {
        $policy = new RetentionPolicy(keepLast: 3, keepMonthly: 6);

        $this->assertSame(['keepLast' => 3, 'keepMonthly' => 6], $policy->toArray());
    }

    public function test_requires_at_least_one_rule(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('mindestens eine Aufbewahrungsregel');

        new RetentionPolicy;
    }

    public function test_rejects_negative_values(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('nicht negativ');

        new RetentionPolicy(keepLast: 3, keepDaily: -1);
    }

    public function test_rejects_policy_that_keeps_nothing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('alle Archive entfernen');

        new RetentionPolicy(keepLast: 0, keepDaily: 0);
    }

    public function test_allows_zero_when_another_rule_keeps_archives(): void
    {
        $policy = new RetentionPolicy(keepLast: 0, keepDaily: 2);

        $this->assertSame(['keepLast' => 0, 'keepDaily' => 2], $policy->toArray());
    }

    public function test_creates_policy_from_config_array(): void
    {
        $policy = RetentionPolicy::fromArray([
            'last' => 3,
            'daily' => '7',
            'weekly' => null,
            'yearly' => 2,
        ]);

        $this->assertSame(['keepLast' => 3, 'keepDaily' => 7, 'keepYearly' => 2], $policy->toArray());
    }

    public function test_rejects_unknown_config_key(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unbekannte Aufbewahrungsregel "hourly"');

        RetentionPolicy::fromArray(['hourly' => 4]);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidValues(): array
    {
        return [
            'text' => ['abc'],
            'float' => [1.5],
            'bool' => [true],
            'array' => [[1]],
        ];
    }

    #[DataProvider('invalidValues')]
    public function test_rejects_non_integer_config_value(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('muss eine ganze Zahl sein');

        RetentionPolicy::fromArray(['last' => $value]);
    }
}
