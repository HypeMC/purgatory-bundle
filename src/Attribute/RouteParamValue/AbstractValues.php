<?php

declare(strict_types=1);

namespace Sofascore\PurgatoryBundle\Attribute\RouteParamValue;

abstract class AbstractValues implements ValuesInterface
{
    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'type' => static::type(),
            'values' => $this->getValues(),
        ];
    }
}
