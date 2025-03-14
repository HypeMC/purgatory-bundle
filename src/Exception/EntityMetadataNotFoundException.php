<?php

declare(strict_types=1);

namespace Sofascore\PurgatoryBundle\Exception;

final class EntityMetadataNotFoundException extends RuntimeException
{
    private const MESSAGE = 'Unable to retrieve metadata for entity "%s".';

    public function __construct(
        public readonly string $class,
    ) {
        parent::__construct(
            message: \sprintf(self::MESSAGE, $class),
        );
    }
}
