<?php

namespace Karross\Routes;

use Karross\Actions\Action;
use Karross\Config\KarrossConfig;
use Karross\Metadata\EntityMetadata;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteGenerator
{
    public function __construct(
        private readonly KarrossConfig $config,
        private readonly RoutePattern $routePattern,
    ) {
    }

    /**
     * @param EntityMetadata[] $metadata
     */
    public function generate(array $metadata): RouteCollection
    {
        $routesCollection = new RouteCollection();

        foreach ($metadata as $fqcn => $entityMetadata) {
            foreach (Action::cases() as $action) {
                $pattern = $this->config->routePattern($action->value);
                $this->routePattern->validate($pattern);

                $path = $this->routePattern->resolve(
                    $pattern,
                    $this->config->routePrefix(),
                    $entityMetadata->slug,
                    $entityMetadata->getIdentifier(),
                );

                $routesCollection->add(self::routeName($fqcn, $action), new Route($path, defaults: ['_controller' => $action->controller()], options: ['fqcn' => $fqcn, 'karross_action' => $action->value], methods: $action->httpMethods()));
            }
        }

        return $routesCollection;
    }

    public static function routeName(string $fqcn, Action $action): string
    {
        return strtolower(str_replace('\\', '_', $fqcn).'_'.$action->name);
    }
}
