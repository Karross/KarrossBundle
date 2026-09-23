<?php

namespace Tests\Unit\Formatters;

use Karross\Formatters\DateTime\DateFormatter;
use Karross\Formatters\DateTime\DateTimeFormatter;
use Karross\Formatters\DateTime\TimeFormatter;
use Karross\Formatters\FormattingContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DateTimeFormatterPresetsTest extends TestCase
{
    #[DataProvider('dateCases')]
    public function testDateFormatterPriorityPatternThenPresetThenDefault(?string $pattern, ?string $preset, string $expected): void
    {
        $formatter = new DateFormatter();
        $context = FormattingContext::forLocale('fr')->with(
            dateFormat: $pattern,
            dateFormatPreset: $preset,
        );

        self::assertSame($expected, $formatter->format(self::date(), $context));
    }

    /**
     * @return iterable<string, array{?string, ?string, string}>
     */
    public static function dateCases(): iterable
    {
        yield 'pattern wins over preset' => ["d MMMM yyyy 'à' HH:mm", 'short', '14 septembre 2026 à 15:30'];
        yield 'preset short without pattern' => [null, 'short', '14/09/2026'];
        yield 'preset full without pattern' => [null, 'full', 'lundi 14 septembre 2026'];
        yield 'default medium when nothing set' => [null, null, '14 sept. 2026'];
    }

    #[DataProvider('timeCases')]
    public function testTimeFormatterPriorityPatternThenPresetThenDefault(?string $pattern, ?string $preset, string $expected): void
    {
        $formatter = new TimeFormatter();
        $context = FormattingContext::forLocale('fr')->with(
            timeFormat: $pattern,
            timeFormatPreset: $preset,
        );

        self::assertSame($expected, $formatter->format(self::date(), $context));
    }

    /**
     * @return iterable<string, array{?string, ?string, string}>
     */
    public static function timeCases(): iterable
    {
        yield 'pattern wins' => ['HH:mm:ss', 'short', '15:30:00'];
        yield 'preset long' => [null, 'long', '15:30:00 UTC'];
        yield 'default short' => [null, null, '15:30'];
    }

    public function testDateTimeFormatterUsesBothLengthPresets(): void
    {
        $formatter = new DateTimeFormatter();
        $context = FormattingContext::forLocale('fr')->with(
            dateFormatPreset: 'long',
            timeFormatPreset: 'short',
        );

        self::assertSame('14 septembre 2026 à 15:30', $formatter->format(self::date(), $context));
    }

    public function testDateTimeFormatterPatternWinsOverPresets(): void
    {
        $formatter = new DateTimeFormatter();
        $context = FormattingContext::forLocale('fr')->with(
            dateTimeFormat: 'yyyy-MM-dd HH:mm',
            dateFormatPreset: 'full',
            timeFormatPreset: 'full',
        );

        self::assertSame('2026-09-14 15:30', $formatter->format(self::date(), $context));
    }

    public function testDateTimeFormatterDefaultStaysMediumShort(): void
    {
        $formatter = new DateTimeFormatter();

        self::assertSame(
            '14 sept. 2026, 15:30',
            $formatter->format(self::date(), FormattingContext::forLocale('fr')),
        );
    }

    private static function date(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-09-14 15:30:00');
    }
}
