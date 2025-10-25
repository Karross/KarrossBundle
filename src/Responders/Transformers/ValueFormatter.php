<?php

namespace Karross\Responders\Transformers;

use DateTimeInterface;
use IntlDateFormatter;
use Stringable;
use UnitEnum;

/**
 * Transforms whatever PHP value into something displayable.
 *
 * - Handles scalars, enums, DateTimeInterface, stringable objets, iterables
 * - Cuts long arrays
 * - Detects recursivity
 */
class ValueFormatter
{
    private const int MAX_DEPTH = 3;
    private const int MAX_ITEMS = 10;

    public function __construct(
        private readonly string $defaultLocale = 'fr_FR',
    ) {}

    public function format(mixed $value, ?string $locale = null, int $depth = 0, array &$seen = []): string
    {
        // Sécurité profondeur
        if ($depth > self::MAX_DEPTH) {
            return '…';
        }

        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name ?? $value->value;
        }

        if ($value instanceof DateTimeInterface) {
            $formatter = new IntlDateFormatter(
                $locale ?? $this->defaultLocale,
                IntlDateFormatter::MEDIUM,
                IntlDateFormatter::SHORT
            );
            return $formatter->format($value);
        }

        // Gestion des tableaux et itérables
        if (is_iterable($value)) {
            $hash = spl_object_id((object)$value);
            if (isset($seen[$hash])) {
                return '[↻]';
            }
            $seen[$hash] = true;

            $parts = [];
            $i = 0;

            foreach ($value as $key => $v) {
                $i++;
                $formatted = $this->format($v, $locale, $depth + 1, $seen);
                $parts[] = "{$key}: {$formatted}";
                if ($i >= self::MAX_ITEMS) {
                    $parts[] = '…';
                    break;
                }
            }

            unset($seen[$hash]);

            return '[' . implode(', ', $parts) . ']';
        }

        return 'N/A';
    }
}
