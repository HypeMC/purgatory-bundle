<?php

declare(strict_types=1);

namespace Sofascore\PurgatoryBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class AsExpressionLanguageFunction
{
    public function __construct(
        public readonly string $functionName,
    ) {
    }
}
