<?php

namespace Karross\Config;

/**
 * Root slice of the Karross config (everything except `entities.*`).
 *
 *     karross:
 *       output:
 *         api: true
 *         html: twig
 *       routes:
 *         prefix: admin
 *       datetime_formats:
 *         compact: "yyyy-MM-dd"
 *
 * Per-entity lookups live in {@see EntityConfig}.
 */
final class KarrossConfig
{
    /**
     * @param array{
     *   output?: array{api?: bool, html?: string},
     *   routes?: array{prefix?: string, index?: string, show?: string, home?: string},
     *   home?: array{show_documentation?: bool},
     *   datetime_formats?: array<string, array<string, string>|string>,
     *   entities?: array<string, array{
     *     actions?: string[],
     *     slug?: string,
     *     properties?: array<string, array{formatter?: string, formatter_options?: array<string, string|bool|null>}>
     *   }>
     * } $config
     */
    public function __construct(private array $config)
    {
    }

    public function apiEnabled(): bool
    {
        return $this->config['output']['api'] ?? true;
    }

    public function htmlRenderer(): string
    {
        return $this->config['output']['html'] ?? 'twig';
    }

    public function homeShowDocumentation(): bool
    {
        return $this->config['home']['show_documentation'] ?? true;
    }

    /**
     * Host-defined named datetime formats: name → (locale → ICU pattern).
     *
     * Both config shapes normalize to the same structure:
     *
     *     karross:
     *       datetime_formats:
     *         compact: "yyyy-MM-dd"              # string form
     *         business:                          # locale → pattern map
     *           fr: "d MMMM yyyy 'à' HH:mm"
     *           default: "yyyy-MM-dd HH:mm"
     *
     * @return array<string, array<string, string>>
     */
    public function datetimeFormats(): array
    {
        $formats = $this->config['datetime_formats'] ?? [];

        return array_filter(
            array_map($this->normalizeDatetimeFormat(...), $formats),
            static fn (?array $definition): bool => null !== $definition,
        );
    }

    /**
     * @return array<string, string>|null null when the value is neither config shape
     */
    private function normalizeDatetimeFormat(mixed $definition): ?array
    {
        if (\is_string($definition)) {
            return ['default' => $definition];
        }

        if (!\is_array($definition)) {
            return null;
        }

        return array_filter(
            $definition,
            static fn (mixed $pattern, mixed $locale): bool => \is_string($locale) && \is_string($pattern),
            \ARRAY_FILTER_USE_BOTH,
        );
    }

    public function routePrefix(): string
    {
        return $this->config['routes']['prefix'] ?? 'admin';
    }

    public function routePattern(string $action): string
    {
        return $this->config['routes'][$action] ?? self::defaultRoutePattern($action);
    }

    public static function defaultRoutePattern(string $action): string
    {
        return match ($action) {
            'index' => '/{prefix}/{slug}',
            'show' => '/{prefix}/{slug}/{identifiers}',
            'home' => '/{prefix}',
            default => throw new \InvalidArgumentException("Unknown Karross route action \"$action\"."),
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->config;
    }
}
