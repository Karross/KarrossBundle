<?php

namespace Karross\Routes;

use Doctrine\ORM\Mapping\ClassMetadata;
use Karross\Actions\Action;
use Karross\Config\KarrossConfig;
use Karross\Exceptions\EntityShortnameException;
use Karross\Metadata\EntityMetadata;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteGenerator
{
    /**
     * @param EntityMetadata[] $metadata
     */
    public function generate(array $metadata): RouteCollection
    {
        $routesCollection = new RouteCollection();

        foreach ($metadata as $fqcn => $entityMetadata) {
            foreach (Action::cases() as $action) {
                $routeName = strtolower(str_replace('\\', '_', $fqcn) . '_' . $action->name);

                $routesCollection->add($routeName, new Route($action->routePattern($entityMetadata->slug, $entityMetadata->classMetadata->getIdentifier()), defaults: ['_controller' => $action->controller()], options: ['fqcn' => $fqcn, 'karross_action' => $action->value], methods: $action->httpMethods()));
            }
        }

        return $routesCollection;
    }
}
