<?php

/**
 * This class registers routes for the application depending on the entities' configuration.
 */

namespace Karross\Routes;

use Karross\Config\KarrossConfig;
use Karross\Metadata\EntityMetadataRegistry;
use Karross\Pages\Home;
use Symfony\Component\Config\Loader\Loader as SFLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteLoader extends SFLoader
{
    public function __construct(
        private readonly RouteGenerator $routeGenerator,
        private readonly EntityMetadataRegistry $entityMetadataRegistry,
        private readonly KarrossConfig $config,
        private readonly RoutePattern $routePattern,
    ) {
        parent::__construct();
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return 'karross.routes' === $type;
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        $routes = $this->routeGenerator->generate($this->entityMetadataRegistry->all());

        $homePattern = $this->config->routePattern('home');
        $this->routePattern->validate($homePattern);

        $routes->add('karross_home', new Route(
            $this->routePattern->resolve($homePattern, $this->config->routePrefix(), '', []),
            defaults: ['_controller' => Home::class],
            methods: ['GET'],
        ));

        return $routes;
    }
}
