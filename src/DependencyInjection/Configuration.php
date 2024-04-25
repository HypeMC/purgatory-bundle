<?php

declare(strict_types=1);

namespace Sofascore\PurgatoryBundle2\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sofascore_purgatory');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('route_ignore_patterns')
                    ->info('Route names that match given regular expressions will be ignored by purgatory.')
                    ->example(['/^_profiler/', '/^_wdt/'])
                    ->scalarPrototype()
                    ->defaultValue([])
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
