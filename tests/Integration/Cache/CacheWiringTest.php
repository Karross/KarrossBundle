<?php

namespace Integration\Cache;

use Karross\Metadata\EntityMetadataBuilder;
use Karross\Metadata\EntityMetadataRegistry;
use Karross\Twig\TemplateRegistry;
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
        /** @var EntityMetadataBuilder $builder */
        $builder = $services->get(EntityMetadataBuilder::class);
        $this->assertEquals($metadata->all(), $builder->buildAllMetadata());

        /** @var TemplateRegistry $templates */
        $templates = $services->get(TemplateRegistry::class);
        $this->assertEquals($templates->all(), $templates->all());
    }

    public function testCachedValuesAreWrittenToThePool(): void
    {
        $services = $this->bootServices('prod', false);

        /** @var EntityMetadataRegistry $registry */
        $registry = $services->get(EntityMetadataRegistry::class);
        $registry->all();
        /** @var TemplateRegistry $templates */
        $templates = $services->get(TemplateRegistry::class);
        $templates->all();

        /** @var CacheItemPoolInterface $pool */
        $pool = $services->get('cache.app');
        $this->assertTrue($pool->getItem('karross.metadata')->isHit());
        $this->assertTrue($pool->getItem('karross.templates')->isHit());
    }

    public function testCacheSurvivesAcrossKernelBoots(): void
    {
        $first = $this->bootServices('prod', false);
        /** @var EntityMetadataBuilder $builder */
        $builder = $first->get(EntityMetadataBuilder::class);
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

        /** @var TemplateRegistry $templates */
        $templates = $services->get(TemplateRegistry::class);
        $this->assertNotEmpty($templates->all());
        $this->assertEquals($templates->all(), $templates->all());

        /** @var CacheItemPoolInterface $pool */
        $pool = $services->get('cache.app');
        $this->assertFalse($pool->getItem('karross.metadata')->isHit());
        $this->assertFalse($pool->getItem('karross.templates')->isHit());
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
