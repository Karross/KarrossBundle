<?php

namespace E2e\Shared;

use CommerceGuys\Intl\Formatter\NumberFormatter as CommerceNumberFormatter;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use Playwright\Locator\LocatorInterface;
use Playwright\Page\PageInterface;

trait TableCellComparisons
{
    private function assertCellEquals(PageInterface $page, string $columnName, string $expected, int $row = 0): void
    {
        self::assertSame($expected, $this->cellForColumn($page, $columnName, $row));
    }

    private function cellForColumn(PageInterface $page, string $columnName, int $row = 0): string
    {
        return trim($this->cellLocatorForColumn($page, $columnName, $row)->innerText());
    }

    private function cellLocatorForColumn(PageInterface $page, string $columnName, int $row = 0): LocatorInterface
    {
        $headers = $page->locator('table.k-table thead th');

        $index = null;
        for ($i = 0, $count = $headers->count(); $i < $count; ++$i) {
            if (trim($headers->nth($i)->innerText()) === $columnName) {
                $index = $i;
                break;
            }
        }

        self::assertNotNull($index, "Column '$columnName' not found in table header");
        $line = $page->locator('table.k-table tbody tr')->nth($row);

        return $line->locator('td')->nth($index);
    }

    private function formatDate(\DateTimeImmutable $value, bool $dateOnly = false): string
    {
        $dateType = \IntlDateFormatter::MEDIUM;
        $timeType = $dateOnly ? \IntlDateFormatter::NONE : \IntlDateFormatter::SHORT;
        $formatter = new \IntlDateFormatter('en_US', $dateType, $timeType);

        return $formatter->format($value) ?: 'N/A';
    }

    private function formatDateWith(string $locale, \DateTimeImmutable $value): string
    {
        $formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT);

        return $formatter->format($value) ?: 'N/A';
    }

    private function formatCount(int $viewCount): string
    {
        $formatter = new CommerceNumberFormatter(new NumberFormatRepository(), ['locale' => 'en']);

        return $formatter->format((string) $viewCount);
    }
}
