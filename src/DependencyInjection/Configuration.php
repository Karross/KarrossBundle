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
                ->arrayNode('routes')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('prefix')
                            ->defaultValue('admin')
                        ->end()
                        ->scalarNode('index')
                            ->defaultValue('/{prefix}/{slug}')
                        ->end()
                        ->scalarNode('show')
                            ->defaultValue('/{prefix}/{slug}/{identifiers}')
                        ->end()
                    ->end()
                ->end()
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
                            ->ifTrue(static fn ($v) => \is_array($v) && array_keys($v) === range(0, \count($v) - 1))
                            ->then(static fn ($v) => ['actions' => $v])
                        ->end()
                        ->beforeNormalization()
                            ->ifNull()
                            ->then(static fn () => []) // transforms ~ into []
                        ->end()
                        ->children()
                            ->arrayNode('actions')
                                ->scalarPrototype()->end()
                                ->defaultValue([])
                            ->end()
                            ->stringNode('slug')
                            ->end()
                            ->arrayNode('properties')
                                ->useAttributeAsKey('property')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('formatter')->defaultNull()->end()
                                        ->arrayNode('formatter_options')
                                            ->addDefaultsIfNotSet()
                                            ->children()
                                                ->scalarNode('currency')->defaultNull()->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
