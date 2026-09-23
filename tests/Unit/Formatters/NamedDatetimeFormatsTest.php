<?php

namespace Tests\Unit\Formatters;

use Karross\Config\KarrossConfig;
use Karross\Formatters\DateTime\DateFormatter;
use Karross\Formatters\DateTime\DateTimeFormatter;
use Karross\Formatters\DateTime\TimeFormatter;
use Karross\Formatters\Options\NamedDatetimeFormats;
use PHPUnit\Framework\TestCase;

final class NamedDatetimeFormatsTest extends TestCase
{
    public function testBuiltInDefaultsMapToIcuLengths(): void
    {
        $formats = $this->formats();

        self::assertSame(['presets' => ['date' => 'medium']], $formats->configFor('k_medium_date'));
        self::assertSame(['presets' => ['time' => 'short']], $formats->configFor('k_short_time'));
        self::assertSame(
            ['presets' => ['date' => 'medium', 'time' => 'short']],
            $formats->configFor('k_medium_datetime'),
        );
        self::assertSame(['presets' => ['date' => 'short']], $formats->configFor('k_short_date'));
        self::assertSame(['presets' => ['date' => 'full', 'time' => 'long']], $formats->configFor('k_full_datetime'));
    }

    public function testHostDefinitionWinsAndMergesWithBuiltInPreset(): void
    {
        $formats = $this->formats([
            'k_medium_date' => ['fr' => 'd MMMM yyyy'],
            'my_custom' => ['fr' => 'dd/MM/yyyy', 'default' => 'yyyy-MM-dd'],
        ]);

        self::assertSame(
            [
                'patterns' => ['fr' => 'd MMMM yyyy'],
                'presets' => ['date' => 'medium'],
            ],
            $formats->configFor('k_medium_date'),
        );
        self::assertSame(
            ['patterns' => ['fr' => 'dd/MM/yyyy', 'default' => 'yyyy-MM-dd']],
            $formats->configFor('my_custom'),
        );
    }

    public function testStringFormConfigIsNormalizedToDefaultKey(): void
    {
        $formats = $this->formats(['string_form' => 'yyyy-MM-dd']);

        self::assertSame(
            ['patterns' => ['default' => 'yyyy-MM-dd']],
            $formats->configFor('string_form'),
        );
    }

    public function testUnknownNameReturnsNull(): void
    {
        self::assertNull($this->formats()->configFor('does_not_exist'));
    }

    public function testImplicitNamesCoverTheThreeDateTimeFormatters(): void
    {
        self::assertSame('k_medium_date', NamedDatetimeFormats::IMPLICIT_BY_FORMATTER[DateFormatter::class]);
        self::assertSame('k_short_time', NamedDatetimeFormats::IMPLICIT_BY_FORMATTER[TimeFormatter::class]);
        self::assertSame('k_medium_datetime', NamedDatetimeFormats::IMPLICIT_BY_FORMATTER[DateTimeFormatter::class]);
        self::assertCount(12, NamedDatetimeFormats::DEFAULT_FORMATS);
    }

    /**
     * @param array<string, array<string, string>|string> $datetimeFormats
     */
    private function formats(array $datetimeFormats = []): NamedDatetimeFormats
    {
        return new NamedDatetimeFormats(new KarrossConfig(['datetime_formats' => $datetimeFormats]));
    }
}
