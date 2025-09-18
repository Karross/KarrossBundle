<?php

namespace Karross\Routes;

use Doctrine\ORM\Mapping\ClassMetadata;
use Karross\Actions\Action;
use Karross\Exceptions\UnableToCreateRoutesException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteGenerator
{
    /**
     * @param ClassMetadata[] $metadata
     */
    public function resolve(array $metadata, array $config): RouteCollection
    {
        $routesCollection = new RouteCollection();

        $resolvedEntities = [];
        foreach ($metadata as $meta) {
            $fqcn = $meta->getName();
            $shortname = $config[$fqcn]['slug'] ?? strtolower($meta->getReflectionClass()->getShortName());
            if (in_array($shortname, $resolvedEntities)) {
                if (isset($config[$fqcn]['slug'])) {
                    throw new UnableToCreateRoutesException(
                        resource: $fqcn,
                        message : sprintf(
                            "The slug you have provided for %s is already in use with %s",
                            $fqcn,
                            array_search($config[$fqcn]['slug'], $resolvedEntities),
                        ),
                    );
                }
                throw new UnableToCreateRoutesException(
                    resource: $fqcn,
                    message: sprintf(
                    "Those classes (%s, %s) have the same shortname '%s'. Please provide a slug to solve the conflicts",
                        $fqcn,
                        array_search($shortname, $resolvedEntities),
                        $shortname
                    )
                );
            }
            $resolvedEntities[$fqcn] = $shortname;
        }

        foreach ($resolvedEntities as $fqcn => $shortname) {
            foreach (Action::cases() as $action) {
                $routeName = strtolower(str_replace('\\', '_', $fqcn) . '_' . $action->name);

                $routesCollection->add($routeName, new Route($action->routePattern($shortname), options: ['fqcn' => $fqcn, '_controller' => $action->controller()], methods: $action->httpMethods()));
            }
        }

        return $routesCollection;
    }
}
