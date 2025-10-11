<?php

namespace Karross\Actions;

use Doctrine\Persistence\ManagerRegistry;
use Karross\Actions\ActionContext;
use Karross\Metadata\EntityMetadataRegistry;
use Karross\Responders\ResponderManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class Index
{
    public function __construct(
        private EntityMetadataRegistry $entityMetadataRegistry,
        private ManagerRegistry $managerRegistry,
        private ResponderManager $responderManager,
        private RouterInterface $router,
    ) {}

    public function __invoke(Request $request): Response
    {
        $routeName = $request->attributes->get('_route');
        $route = $this->router->getRouteCollection()->get($routeName);
        $fqcn = $route->getOption('fqcn');
        $action = $route->getOption('karross_action');
        $repository = $this->managerRegistry->getManagerForClass($fqcn)->getRepository($fqcn);
        $entities = $repository->findAll();
        $entityMetadata = $this->entityMetadataRegistry->get($fqcn);

        return $this->responderManager->getResponse(new ActionContext($request, $action, $entityMetadata->getSlug()), ['items' => $entities, 'entityMetadata' => $entityMetadata]);
    }
}
