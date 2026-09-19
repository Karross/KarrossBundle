<?php

namespace Karross\Metadata\Computed;

use Karross\Metadata\Collect\ComputedMetadataBuilder;
use Symfony\Contracts\Cache\CacheInterface;

class EntityMetadataRegistry
{
    private readonly bool $cacheEnabled;

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly ComputedMetadataBuilder $builder,
        bool $debug,
    ) {
        $this->cacheEnabled = !$debug;
    }

    /** @return EntityMetadata[] */
    public function all(): array
    {
        return $this->cacheEnabled
            ? $this->cache->get('karross.metadata', fn () => $this->builder->buildAllMetadata())
            : $this->builder->buildAllMetadata();
    }

    public function get(string $fqcn): EntityMetadata
    {
        $entityMetadata = $this->all()[$fqcn] ?? null;
        if (!$entityMetadata instanceof EntityMetadata) {
            throw new \LogicException(\sprintf('%s does not belong to the list of entities managed by doctrine, which are : %s', $fqcn, implode(',', array_keys($this->all()))));
        }

        return $entityMetadata;
    }

    public function getBySlug(string $slug): EntityMetadata
    {
        foreach ($this->all() as $entityMetadata) {
            if ($slug === $entityMetadata->slug) {
                return $entityMetadata;
            }
        }

        throw new \LogicException(\sprintf('No entity is managed by doctrine with the slug "%s".', $slug));
    }
}
