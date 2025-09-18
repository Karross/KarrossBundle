<?php

namespace Karross\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('karross');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('output')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('api')
                            ->defaultTrue()
                        ->end()
                        ->enumNode('html')
                            ->values(['twig', 'vue', 'react'])
                            ->defaultValue('twig')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('entities')
                    ->useAttributeAsKey('pattern')
                    ->arrayPrototype()
                        ->beforeNormalization()
                            ->ifTrue(fn($v) => is_array($v) && array_keys($v) === range(0, count($v) - 1))
                            ->then(fn($v) => ['actions' => $v])
                        ->end()
                        ->beforeNormalization()
                            ->ifNull()
                            ->then(fn() => []) // transforms ~ into []
                        ->end()
                        ->children()
                            ->arrayNode('actions')
                                ->scalarPrototype()->end()
                                ->defaultValue([])
                            ->end()
                            ->stringNode('slug')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
