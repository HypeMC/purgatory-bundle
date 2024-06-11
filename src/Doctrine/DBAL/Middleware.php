<?php

declare(strict_types=1);

namespace Sofascore\PurgatoryBundle2\Doctrine\DBAL;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;
use Sofascore\PurgatoryBundle2\Listener\EntityChangeListener;

final class Middleware implements MiddlewareInterface
{
    public function __construct(
        private readonly EntityChangeListener $entityChangeListener,
    ) {
    }

    public function wrap(Driver $driver): Driver
    {
        return new PurgatoryDriver(
            driver: $driver,
            entityChangeListener: $this->entityChangeListener,
        );
    }
}
