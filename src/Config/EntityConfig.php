<?php

namespace Karross\Config;

/**
 * Per-entity slice of the Karross config (`entities.*`).
 *
 *     karross:
 *       entities:
 *         App\Entity\Article:
 *           slug: articles
 *           properties:
 *             price:
 *               formatter: Karross\Formatters\IntlCurrencyFormatter
 *               formatter_options:
 *                 currency: EUR
 */
final class EntityConfig
{
    /**
     * @param array{
     *   entities?: array<string, array{
     *     actions?: string[],
     *     slug?: string,
     *     properties?: array<string, array{formatter?: string, formatter_options?: array<string, string|bool|int|null>}>
     *   }>
     * } $config full `karross.config` parameter; only `entities` is read
     */
    public function __construct(private array $config)
    {
    }

    /**
     * @return array<string, array{
     *   actions?: string[],
     *   slug?: string,
     *   properties?: array<string, array{formatter?: string, formatter_options?: array<string, string|bool|int|null>}>
     * }>
     */
    public function all(): array
    {
        return $this->config['entities'] ?? [];
    }

    /**
     * @return array{
     *   actions?: string[],
     *   slug?: string,
     *   properties?: array<string, array{formatter?: string, formatter_options?: array<string, string|bool|int|null>}>
     * }
     */
    public function forClass(string $fqcn): array
    {
        return $this->all()[$fqcn] ?? [];
    }

    /**
     * @return string[]
     */
    public function actions(string $fqcn): array
    {
        return $this->forClass($fqcn)['actions'] ?? [];
    }

    public function propertyFormatter(string $fqcn, string $property): ?string
    {
        return $this->forClass($fqcn)['properties'][$property]['formatter'] ?? null;
    }

    /**
     * @return array<string, string|bool|int>
     */
    public function propertyFormatterOptions(string $fqcn, string $property): array
    {
        $options = $this->forClass($fqcn)['properties'][$property]['formatter_options'] ?? null;
        if (null === $options) {
            return [];
        }

        return array_filter(
            $options,
            static fn (mixed $value): bool => \is_string($value) || \is_bool($value) || \is_int($value),
        );
    }

    public function slug(string $fqcn): ?string
    {
        return $this->forClass($fqcn)['slug'] ?? null;
    }
}
