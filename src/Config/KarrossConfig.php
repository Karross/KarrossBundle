<?php

namespace Karross\Config;

/**
 * @param array{
 *   output?: array{api?: bool, html?: string},
 *   routes?: array{prefix?: string, index?: string, show?: string},
 *   entities?: array<string, array{
 *     actions?: string[],
 *     slug?: string,
 *     properties?: array<string, array{formatter?: string, formatter_options?: array<string, string|bool|null>}>
 *   }>
 * } $config
 */
final class KarrossConfig
{
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

    public function entities(): array
    {
        return $this->config['entities'] ?? [];
    }

    /**
     * @return array{
     *   actions?: string[],
     *   slug?: string,
     *   properties?: array<string, array{formatter?: string, formatter_options?: array<string, string|bool|null>}>
     * }
     */
    public function entityConfig(string $fqcn): array
    {
        return $this->config['entities'][$fqcn] ?? [];
    }

    public function entityActions(string $fqcn): array
    {
        return $this->entityConfig($fqcn)['actions'] ?? [];
    }

    public function entityPropertyFormatter(string $fqcn, string $property): ?string
    {
        return $this->entityConfig($fqcn)['properties'][$property]['formatter'] ?? null;
    }

    /**
     * @return array<string, string|bool>
     */
    public function entityPropertyFormatterOptions(string $fqcn, string $property): array
    {
        $options = $this->entityConfig($fqcn)['properties'][$property]['formatter_options'] ?? null;
        if (!\is_array($options)) {
            return [];
        }

        $formatterOptions = [];
        foreach ($options as $key => $value) {
            if (\is_string($key) && (\is_string($value) || \is_bool($value))) {
                $formatterOptions[$key] = $value;
            }
        }

        return $formatterOptions;
    }

    public function entitySlug(string $fqcn): ?string
    {
        return $this->entityConfig($fqcn)['slug'] ?? null;
    }

    public function routePrefix(): string
    {
        $routes = $this->config['routes'] ?? [];
        if (!\is_array($routes)) {
            return 'admin';
        }
        $prefix = $routes['prefix'] ?? 'admin';

        return \is_string($prefix) ? $prefix : 'admin';
    }

    public function routePattern(string $action): string
    {
        $routes = $this->config['routes'] ?? [];
        $pattern = \is_array($routes) ? ($routes[$action] ?? null) : null;

        return \is_string($pattern) ? $pattern : self::defaultRoutePattern($action);
    }

    public static function defaultRoutePattern(string $action): string
    {
        return match ($action) {
            'index' => '/{prefix}/{slug}',
            'show' => '/{prefix}/{slug}/{identifiers}',
            default => throw new \InvalidArgumentException("Unknown Karross route action \"$action\"."),
        };
    }

    public function raw(): array
    {
        return $this->config;
    }
}
