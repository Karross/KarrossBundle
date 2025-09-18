<?php
/**
 * This class registers routes for the application depending on the entities configuration.
 */
namespace Karross\Routes;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Config\Loader\Loader as SFLoader;
use Symfony\Component\Routing\RouteCollection;

class RouteLoader extends SFLoader
{
    public function __construct(
        private readonly RouteGenerator  $routeGenerator,
        private readonly ManagerRegistry $managerRegistry,
        private readonly array           $config = []
    ) {
        parent::__construct();
    }
    public function supports(mixed $resource, string $type = null): bool
    {
        return $type === 'karross.routes';
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        $metadata = [];
        foreach ($this->managerRegistry->getManagers() as $em) {
            $metadata = array_merge($metadata, $em->getMetadataFactory()->getAllMetadata());
        }
        $config = $this->config['entities'] ?? [];

        return $this->routeGenerator->resolve($metadata, $config);
    }
}
