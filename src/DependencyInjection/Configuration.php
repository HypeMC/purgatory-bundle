<?php

declare(strict_types=1);

namespace Sofascore\PurgatoryBundle\DependencyInjection;

use Doctrine\ORM\Events as DoctrineEvents;
use Sofascore\PurgatoryBundle\Purger\PurgerInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Messenger\MessageBusInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('purgatory');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->fixXmlConfig('mapping_path')
            ->fixXmlConfig('route_ignore_pattern')
            ->children()
                ->arrayNode('mapping_paths')
                    ->info('List of files or directories where Purgatory will look for additional purge definitions.')
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('route_ignore_patterns')
                    ->info('Route names that match the given regular expressions will be ignored.')
                    ->example(['/^_profiler/', '/^_wdt/'])
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('doctrine_middleware')
                    ->canBeDisabled()
                    ->children()
                        ->integerNode('priority')
                            ->info('Explicitly set the priority of Purgatory\'s Doctrine middleware.')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('doctrine_event_listener_priorities')
                    ->info('Explicitly set the priorities of Purgatory\'s Doctrine event listener.')
                    ->beforeNormalization()
                        ->ifTrue(static fn (mixed $priority): bool => \is_int($priority))
                        ->then(static fn (int $priority): array => [
                            DoctrineEvents::preRemove => $priority,
                            DoctrineEvents::postPersist => $priority,
                            DoctrineEvents::postUpdate => $priority,
                            DoctrineEvents::postFlush => $priority,
                        ])
                    ->end()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode(DoctrineEvents::preRemove)->defaultNull()->end()
                        ->integerNode(DoctrineEvents::postPersist)->defaultNull()->end()
                        ->integerNode(DoctrineEvents::postUpdate)->defaultNull()->end()
                        ->integerNode(DoctrineEvents::postFlush)
                            ->info('This event is not registered when the Doctrine middleware is enabled.')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('purger')
                    ->fixXmlConfig('host')
                    ->addDefaultsIfNotSet()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(static fn (string $purger): array => ['name' => $purger])
                    ->end()
                    ->validate()
                        ->ifTrue(static fn (array $purger): bool => 'varnish' === $purger['name'] && !class_exists(HttpClient::class))
                        ->thenInvalid('The Varnish purger requires Symfony\'s HTTP client component to be installed. Try running "composer require symfony/http-client".')
                    ->end()
                    ->children()
                        ->scalarNode('name')
                            ->info(\sprintf('The ID of a service that implements the "%s" interface', PurgerInterface::class))
                            ->example('symfony')
                            ->defaultNull()
                        ->end()
                        ->arrayNode('hosts')
                            ->info('The hosts from which URLs should be purged')
                            ->scalarPrototype()
                                ->validate()->always(static fn (string $host): string => rtrim($host, '/'))->end()
                            ->end()
                            ->defaultValue([])
                        ->end()
                        ->scalarNode('http_client')
                            ->info('The service ID of the HTTP client to use, must be an instance of Symfony\'s HTTP client')
                            ->defaultNull()
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('messenger')
                    ->addDefaultsIfNotSet()
                    ->beforeNormalization()
                        ->ifString()
                        ->then(static fn (string $messenger): array => ['transport' => $messenger])
                    ->end()
                    ->validate()
                        ->ifTrue(static fn (array $messenger): bool => !interface_exists(MessageBusInterface::class) && array_filter($messenger))
                        ->thenInvalid('Messenger support cannot be enabled as the component is not installed. Try running "composer require symfony/messenger".')
                    ->end()
                    ->validate()
                        ->ifTrue(static fn (array $messenger): bool => !$messenger['transport'] && $messenger['bus'])
                        ->thenInvalid('Cannot set the messenger bus without defining the transport.')
                    ->end()
                    ->validate()
                        ->ifTrue(static fn (array $messenger): bool => !$messenger['transport'] && $messenger['batch_size'])
                        ->thenInvalid('Cannot set the batch size without defining the transport.')
                    ->end()
                    ->children()
                        ->scalarNode('transport')
                            ->info('Set the name of the messenger transport to use')
                            ->defaultNull()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('bus')
                            ->info('Set the name of the messenger bus to use')
                            ->defaultNull()
                            ->cannotBeEmpty()
                        ->end()
                        ->integerNode('batch_size')
                            ->info('Set the number of urls to dispatch per message')
                            ->defaultNull()
                            ->validate()
                                ->ifTrue(static fn (int $batchSize): bool => !($batchSize > 0))
                                ->thenInvalid('The batch size must be a number greater than 0.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->booleanNode('profiler_integration')
                    ->info('Enables the data collector and profiler panel if the profiler is enabled.')
                    ->defaultTrue()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
