<?php

namespace Integration\Routes;

use Karross\Actions\REST\Index;
use Karross\Exceptions\UnableToCreateRoutesException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;
use TestedApp\Kernel;

class LoaderTest extends TestCase
{
    #[DataProvider('exceptionsProvider')]
    public function testRoutesCannotBeLoaded(string $expectedExceptionType, string $expectedExceptionMessage, array $configFilenames): void
    {
        $filePaths = array_map(
            fn (string $configFilename) => self::pathForFile($configFilename),
            $configFilenames
        );
        $signature = implode('_PLUS_', $configFilenames);
        $kernel = new Kernel('test_'. $signature, true, $filePaths);
        $kernel->boot();

        $this->expectException($expectedExceptionType);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $router = $kernel->getContainer()->get('test.service_container')->get(RouterInterface::class);
        $router->getRouteCollection();
    }
    public static function exceptionsProvider(): \Generator
    {
        yield 'Conflicts with entity shortnames and no Karross configuration' => [
            UnableToCreateRoutesException::class,
            "Those classes (TestedApp\Domain\Entity\Article, TestedApp\Entity\Article) have the same shortname 'article'. Please provide a slug to solve the conflicts",
            [
                'doctrine_with_shortname_entity_conflicts'
            ]
        ];
    }

    #[DataProvider('routesProvider')]
    public function testRoutesAreLoaded(array $expectedRoutes, array $configFilenames): void
    {
        $filePaths = array_map(
            fn (string $configFilename) => self::pathForFile($configFilename),
            $configFilenames
        );
        $signature = implode('_PLUS_', $configFilenames);
        $kernel = new Kernel('test_'. $signature, true, $filePaths);
        $kernel->boot();

        /** @var RouterInterface $router */
        $router = $kernel->getContainer()->get('test.service_container')->get(RouterInterface::class);
        foreach ($expectedRoutes as $routeName => $expectedRoute) {
            $this->assertEquals($expectedRoute, $router->getRouteCollection()->get($routeName));
        };
    }

    public static function routesProvider(): \Generator
    {
        yield 'No conflicts with entity shortnames and no Karross configuration' => [
            [
                'testedapp_entity_article_index' => new Route('/admin/article', options: ['fqcn' => 'TestedApp\Entity\Article', 'utf8' => true, '_controller' => Index::class], methods: ['GET']),
                'testedapp_entity_category_index' => new Route('/admin/category', options: ['fqcn' => 'TestedApp\Entity\Category', 'utf8' => true, '_controller' => Index::class], methods: ['GET']),
            ],
            [
                'doctrine_no_shortname_entity_conflicts'
            ]
        ];

        yield 'Conflicts with entity shortnames resolved by Karross configuration' => [
            [
                'testedapp_entity_article_index' => new Route('/admin/article', options: ['fqcn' => 'TestedApp\Entity\Article', 'utf8' => true, '_controller' => Index::class], methods: ['GET']),
                'testedapp_entity_category_index' => new Route('/admin/category', options: ['fqcn' => 'TestedApp\Entity\Category', 'utf8' => true, '_controller' => Index::class], methods: ['GET']),
                'testedapp_domain_entity_article_index' => new Route('/admin/domain-article', options: ['fqcn' => 'TestedApp\Domain\Entity\Article', 'utf8' => true, '_controller' => Index::class], methods: ['GET']),
            ],
            [
                'doctrine_with_shortname_entity_conflicts',
                'karross_to_resolve_entity_shortname_conflicts'
            ]
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }

    private static function pathForFile(string $configFilename): string
    {
        return sprintf(__DIR__.'/../TestedApp/config/%s.php', $configFilename);
    }
}
