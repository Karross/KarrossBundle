<?php

namespace Karross\Actions;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class Show
{
    public function __invoke(Request $request, ManagerRegistry $managerRegistry, RouterInterface $router): Response
    {
        /**@var \Symfony\Component\Routing\Route $route */
        $routeName = $request->attributes->get('_route');
        $route = $router->getRouteCollection()->get($routeName);
        $fqcn = $route->getOption('fqcn');
        $routeParams = $request->attributes->get('_route_params');
        $entity = $managerRegistry->getManagerForClass($fqcn)->find($fqcn, $routeParams);
        dd($entity);
        return new Response('show');
    }
}
