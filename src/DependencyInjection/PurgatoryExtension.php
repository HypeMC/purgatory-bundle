<?php

declare(strict_types=1);

namespace Sofascore\PurgatoryBundle2\DependencyInjection;

use Sofascore\PurgatoryBundle2\Attribute\PurgeOn;
use Sofascore\PurgatoryBundle2\Cache\PropertyResolver\SubscriptionResolverInterface;
use Sofascore\PurgatoryBundle2\Cache\TargetResolver\TargetResolverInterface;
use Sofascore\PurgatoryBundle2\RouteParamValueResolver\ValuesResolverInterface;
use Sofascore\PurgatoryBundle2\RouteProvider\RouteProviderInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

final class PurgatoryExtension extends ConfigurableExtension
{
    /**
     * @param array<array-key, mixed> $mergedConfig
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__, 2).'/config'));
        $loader->load('services.php');

        $container->registerAttributeForAutoconfiguration(
            PurgeOn::class,
            static function (ChildDefinition $definition, PurgeOn $attribute, \ReflectionClass|\ReflectionMethod $reflection): void {
                $definition->addTag(
                    name: 'purgatory.purge_on',
                    attributes: [
                        'class' => $reflection instanceof \ReflectionMethod ? $reflection->class : $reflection->name,
                    ],
                );
            },
        );

        /** @var array{name: ?string, host: ?string} $purgerConfig */
        $purgerConfig = $mergedConfig['purger'];
        $container->setParameter('.sofascore.purgatory.purger.name', $purgerConfig['name']);
        $container->setParameter('.sofascore.purgatory.purger.host', $purgerConfig['host']);

        $container->getDefinition('sofascore.purgatory.controller_metadata_provider')
            ->setArgument(2, $mergedConfig['route_ignore_patterns']);

        $container->getDefinition('sofascore.purgatory.doctrine_middleware')
            ->addTag(
                name: 'doctrine.middleware',
                attributes: null !== $mergedConfig['doctrine_middleware_priority']
                    ? ['priority' => $mergedConfig['doctrine_middleware_priority']]
                    : [],
            );

        $container->registerForAutoconfiguration(SubscriptionResolverInterface::class)
            ->addTag('purgatory.subscription_resolver');

        $container->registerForAutoconfiguration(TargetResolverInterface::class)
            ->addTag('purgatory.target_resolver');

        $container->registerForAutoconfiguration(RouteProviderInterface::class)
            ->addTag('purgatory.route_provider');

        $container->registerForAutoconfiguration(ValuesResolverInterface::class)
            ->addTag('purgatory.route_param_value_resolver');

        if (!$container->hasDefinition('cache.system')) {
            $container->removeDefinition('sofascore.purgatory.cache.expression_language');
        }
        if (!$container::willBeAvailable('symfony/expression-language', ExpressionLanguage::class, [])) {
            $container->removeDefinition('sofascore.purgatory.expression_language');
        }
    }

    public function getAlias(): string
    {
        return 'sofascore_purgatory';
    }
}
