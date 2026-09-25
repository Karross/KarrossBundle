<?php

namespace Karross\Formatters\Options;

use Karross\Config\KarrossConfig;
use Karross\Formatters\DateTime\DateFormatter;
use Karross\Formatters\DateTime\DateTimeFormatter;
use Karross\Formatters\DateTime\TimeFormatter;

/**
 * The named datetime formats: the 12 built-in ones plus the host's `karross.datetime_formats`.
 * Build-time only — the resolved config is embedded into property metadata, never used at render time.
 */
final readonly class NamedDatetimeFormats
{
    /**
     * The 12 formats shipped with the bundle, mapped to ICU lengths.
     *
     * @var array<string, array<string, string>>
     */
    public const array DEFAULT_FORMATS = [
        'k_short_date' => ['date' => 'short'],
        'k_medium_date' => ['date' => 'medium'],
        'k_long_date' => ['date' => 'long'],
        'k_full_date' => ['date' => 'full'],
        'k_short_time' => ['time' => 'short'],
        'k_medium_time' => ['time' => 'medium'],
        'k_long_time' => ['time' => 'long'],
        'k_full_time' => ['time' => 'full'],
        'k_short_datetime' => ['date' => 'short', 'time' => 'short'],
        'k_medium_datetime' => ['date' => 'medium', 'time' => 'short'],
        'k_long_datetime' => ['date' => 'long', 'time' => 'medium'],
        'k_full_datetime' => ['date' => 'full', 'time' => 'long'],
    ];

    /**
     * Implicit format name per DateTime formatter when the property has no
     * explicit datetime_format option.
     *
     * @var array<class-string, string>
     */
    public const array IMPLICIT_BY_FORMATTER = [
        DateFormatter::class => 'k_medium_date',
        TimeFormatter::class => 'k_short_time',
        DateTimeFormatter::class => 'k_medium_datetime',
    ];

    public function __construct(private KarrossConfig $config)
    {
    }

    /**
     * Returns null when the name is neither host-defined nor built-in —
     * the caller reports which property used the unknown name.
     *
     * Example — "k_medium_date", no host config:
     *   returns ["presets" => ["date" => "medium"]]
     *
     * Example — host redefines "k_medium_date" with a French pattern:
     *   returns ["patterns" => ["fr" => "d MMMM yyyy"],
     *            "presets" => ["date" => "medium"]]
     *
     * Example — "does_not_exist":
     *   returns null
     *
     * @return array{patterns?: array<string, string>, presets?: array<string, string>}|null
     */
    public function configFor(string $name): ?array
    {
        $host = $this->config->datetimeFormats()[$name] ?? null;
        $default = self::DEFAULT_FORMATS[$name] ?? null;

        if (null === $host && null === $default) {
            return null;
        }

        $resolved = [];

        if (null !== $host) {
            $patterns = [];
            foreach ($host as $locale => $pattern) {
                if ('' !== $pattern) {
                    $patterns[$locale] = $pattern;
                }
            }
            if ([] !== $patterns) {
                $resolved['patterns'] = $patterns;
            }
        }

        if (null !== $default) {
            $resolved['presets'] = $default;
        }

        return $resolved;
    }
}
