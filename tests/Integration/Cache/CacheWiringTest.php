<?php

namespace Integration\Cache;

use Karross\Metadata\Collect\ComputedMetadataBuilder;
use Karross\Metadata\Computed\EntityMetadataRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use TestedApp\Kernel;

class CacheWiringTest extends TestCase
{
    private const ENV_PREFIX = 'test_cache';

    public function testRegistriesCacheTheirResultInAProdLikeKernel(): void
    {
        $services = $this->bootServices('prod', false);

        /** @var EntityMetadataRegistry $metadata */
        $metadata = $services->get(EntityMetadataRegistry::class);
        $this->assertEquals($metadata->all(), $metadata->all());
        /** @var ComputedMetadataBuilder $builder */
        $builder = $services->get(ComputedMetadataBuilder::class);
        $this->assertEquals($metadata->all(), $builder->buildAllMetadata());
    }

    public function testCachedValuesAreWrittenToThePool(): void
    {
        $services = $this->bootServices('prod', false);

        /** @var EntityMetadataRegistry $registry */
        $registry = $services->get(EntityMetadataRegistry::class);
        $registry->all();

        /** @var CacheItemPoolInterface $pool */
        $pool = $services->get('cache.app');
        $this->assertTrue($pool->getItem('karross.metadata')->isHit());
    }

    public function testCacheSurvivesAcrossKernelBoots(): void
    {
        $first = $this->bootServices('prod', false);
        /** @var ComputedMetadataBuilder $builder */
        $builder = $first->get(ComputedMetadataBuilder::class);
        $coldBuild = $builder->buildAllMetadata();
        /** @var EntityMetadataRegistry $registry */
        $registry = $first->get(EntityMetadataRegistry::class);
        $registry->all();

        $second = $this->bootServices('prod', false);
        /** @var EntityMetadataRegistry $registry */
        $registry = $second->get(EntityMetadataRegistry::class);
        $this->assertEquals($coldBuild, $registry->all());
    }

    public function testRegistriesSkipTheCacheInADebugKernel(): void
    {
        $services = $this->bootServices('debug', true);

        /** @var EntityMetadataRegistry $metadata */
        $metadata = $services->get(EntityMetadataRegistry::class);
        $this->assertNotEmpty($metadata->all());
        $this->assertEquals($metadata->all(), $metadata->all());

        /** @var CacheItemPoolInterface $pool */
        $pool = $services->get('cache.app');
        $this->assertFalse($pool->getItem('karross.metadata')->isHit());
    }

    private function bootServices(string $suffix, bool $debug): ContainerInterface
    {
        $kernel = new Kernel(
            self::ENV_PREFIX.'_'.$suffix,
            $debug,
            [
                __DIR__.'/../TestedApp/config/doctrine_standard.php',
                __DIR__.'/../TestedApp/config/framework_cache_'.('prod' === $suffix ? 'filesystem' : 'array').'.php',
            ]
        );
        $kernel->boot();

        $services = $kernel->getContainer()->get('test.service_container');
        \assert($services instanceof ContainerInterface);

        return $services;
    }
}
