<?php

namespace Karross\Metadata;

use Symfony\Contracts\Cache\CacheInterface;

class EntityMetadataRegistry
{
    private bool $cacheEnabled;

    public function __construct(
        private CacheInterface $cache,
        private EntityMetadataBuilder $builder,
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
}
