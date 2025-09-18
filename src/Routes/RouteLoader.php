<?php
/**
 * This class registers routes for the application depending on the entities configuration.
 */
namespace Karross\Routes;

use Doctrine\Persistence\ManagerRegistry;
use Karross\Config\KarrossConfig;
use Karross\Metadata\DoctrineMetadataParser;
use Karross\Metadata\EntityMetadataRegistry;
use Symfony\Component\Config\Loader\Loader as SFLoader;
use Symfony\Component\Routing\RouteCollection;

class RouteLoader extends SFLoader
{
    public function __construct(
        private readonly RouteGenerator $routeGenerator,
        private readonly EntityMetadataRegistry $entityMetadataRegistry,
    ) {
        parent::__construct();
    }
    public function supports(mixed $resource, string $type = null): bool
    {
        return $type === 'karross.routes';
    }

    public function load(mixed $resource, ?string $type = null): RouteCollection
    {
        return $this->routeGenerator->generate($this->entityMetadataRegistry->all());
    }
}
