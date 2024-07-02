<?php

declare(strict_types=1);

namespace Sofascore\PurgatoryBundle2\Cache\TargetResolver;

use Sofascore\PurgatoryBundle2\Attribute\Target\TargetInterface;
use Sofascore\PurgatoryBundle2\Cache\RouteMetadata\RouteMetadata;

interface TargetResolverInterface
{
    /**
     * @return class-string<TargetInterface>
     */
    public static function for(): string;

    /**
     * @return list<string>
     */
    public function resolve(TargetInterface $target, RouteMetadata $routeMetadata): array;
}
