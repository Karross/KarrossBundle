<?php

namespace Integration\Formatters;

use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use Karross\Formatters\FormattingContext;
use Karross\Formatters\IntlNumberFormatter;
use PHPUnit\Framework\TestCase;

final class IntlNumberFormatterTest extends TestCase
{
    private IntlNumberFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new IntlNumberFormatter(new NumberFormatRepository());
    }

    public function testLocalizesAnInteger(): void
    {
        self::assertSame('1,234', $this->formatter->format(1234, FormattingContext::forLocale('en_US')));
    }

    public function testLocalizesTheIntegerBounds(): void
    {
        self::assertSame(
            '9,223,372,036,854,775,807',
            $this->formatter->format('9223372036854775807', FormattingContext::forLocale('en_US'))
        );
    }

    public function testLocalizesADecimal(): void
    {
        self::assertSame('49.9', $this->formatter->format('49.90', FormattingContext::forLocale('en_US')));
    }

    public function testRendersNullAsNull(): void
    {
        self::assertNull($this->formatter->format(null, FormattingContext::forLocale('en_US')));
    }
}
