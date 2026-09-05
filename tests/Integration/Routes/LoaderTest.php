<?php

namespace Integration\Routes;

use Karross\Actions\Action;
use Karross\Exceptions\EntityShortnameException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Routing\RouterInterface;
use TestedApp\Kernel;

class LoaderTest extends TestCase
{
    #[DataProvider('exceptionsProvider')]
    public function testRoutesCannotBeLoaded(string $expectedExceptionType, string $expectedExceptionMessage, array $configFilenames): void
    {
        $filePaths = array_map(
            static fn (string $configFilename) => self::pathForFile($configFilename),
            $configFilenames
        );
        $signature = implode('_PLUS_', $configFilenames);
        $kernel = new Kernel('test_'.$signature, true, $filePaths);
        $kernel->boot();

        $this->expectException($expectedExceptionType);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $router = $kernel->getContainer()->get('test.service_container')->get(RouterInterface::class);
        $router->getRouteCollection();
    }

    public static function exceptionsProvider(): \Generator
    {
        yield 'Conflicts with entity shortnames and no Karross configuration' => [
            EntityShortnameException::class,
            "Those classes (TestedApp\Domain\Entity\Article, TestedApp\Entity\Article) have the same shortname 'article'. Please provide a slug to solve the conflicts",
            [
                'doctrine_with_shortname_entity_conflicts',
            ],
        ];

        yield 'Route pattern with unknown token' => [
            LoaderLoadException::class,
            'Unknown token {bogus} in Karross route pattern "/admin/{slug}/{bogus}"',
            [
                'doctrine_no_shortname_entity_conflicts',
                'karross_unknown_route_token',
            ],
        ];
    }

    /**
     * @param string[]              $expectedRouteNames
     * @param array<string, string> $expectedPaths
     */
    #[DataProvider('routesProvider')]
    public function testRoutesAreLoaded(array $expectedRouteNames, array $configFilenames, array $expectedPaths = []): void
    {
        $filePaths = array_map(
            static fn (string $configFilename) => self::pathForFile($configFilename),
            $configFilenames
        );
        $signature = implode('_PLUS_', $configFilenames);
        $kernel = new Kernel('test_'.$signature, true, $filePaths);
        $kernel->boot();

        /** @var RouterInterface $router */
        $router = $kernel->getContainer()->get('test.service_container')->get(RouterInterface::class);
        $routeCollection = $router->getRouteCollection();

        // Exactly the expected routes are registered
        $this->assertSame(
            $expectedRouteNames,
            array_keys($routeCollection->all())
        );

        // Each route is wired to the expected action controller
        foreach ($expectedRouteNames as $routeName) {
            $route = $routeCollection->get($routeName);
            $this->assertNotNull($route, "Route $routeName should exist");
            $action = Action::from($route->getOption('karross_action'));
            $this->assertSame($action->controller(), $route->getDefault('_controller'));
        }

        // Optional per-route path assertions
        foreach ($expectedPaths as $routeName => $expectedPath) {
            $route = $routeCollection->get($routeName);
            $this->assertNotNull($route, "Route $routeName should exist");
            $this->assertSame($expectedPath, $route->getPath());
        }
    }

    public static function routesProvider(): \Generator
    {
        yield 'No conflicts with entity shortnames and no Karross configuration' => [
            [
                'testedapp_entity_article_index',
                'testedapp_entity_article_show',
                'testedapp_entity_category_index',
                'testedapp_entity_category_show',
            ],
            [
                'doctrine_no_shortname_entity_conflicts',
            ],
        ];

        yield 'Conflicts with entity shortnames resolved by Karross configuration' => [
            [
                'testedapp_entity_article_index',
                'testedapp_entity_article_show',
                'testedapp_entity_category_index',
                'testedapp_entity_category_show',
                'testedapp_domain_entity_article_index',
                'testedapp_domain_entity_article_show',
            ],
            [
                'doctrine_with_shortname_entity_conflicts',
                'karross_to_resolve_entity_shortname_conflicts',
            ],
        ];

        yield 'Default routes use the admin prefix' => [
            [
                'testedapp_entity_article_index',
                'testedapp_entity_article_show',
                'testedapp_entity_category_index',
                'testedapp_entity_category_show',
            ],
            [
                'doctrine_no_shortname_entity_conflicts',
            ],
            [
                'testedapp_entity_article_index' => '/admin/article',
                'testedapp_entity_article_show' => '/admin/article/{id}',
            ],
        ];

        yield 'Custom routes prefix is applied' => [
            [
                'testedapp_entity_article_index',
                'testedapp_entity_article_show',
                'testedapp_entity_category_index',
                'testedapp_entity_category_show',
            ],
            [
                'doctrine_no_shortname_entity_conflicts',
                'karross_custom_routes_prefix',
            ],
            [
                'testedapp_entity_article_index' => '/dashboard/article',
                'testedapp_entity_article_show' => '/dashboard/article/{id}',
            ],
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        restore_exception_handler();
    }

    private static function pathForFile(string $configFilename): string
    {
        return \sprintf(__DIR__.'/../TestedApp/config/%s.php', $configFilename);
    }
}
