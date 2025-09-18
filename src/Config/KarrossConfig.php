<?php

namespace Karross\Config;

final class KarrossConfig
{
    public function __construct(private array $config) {}

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

    public function entityConfig(string $fqcn): array
    {
        return $this->config['entities'][$fqcn] ?? [];
    }

    public function entityActions(string $fqcn): array
    {
        return $this->entityConfig($fqcn)['actions'] ?? [];
    }

    public function entitySlug(string $fqcn): ?string
    {
        return $this->entityConfig($fqcn)['slug'] ?? null;
    }

    public function raw(): array
    {
        return $this->config;
    }
}

