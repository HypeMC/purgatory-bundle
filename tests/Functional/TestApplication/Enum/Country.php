<?php

declare(strict_types=1);

namespace Sofascore\PurgatoryBundle\Tests\Functional\TestApplication\Enum;

enum Country: string
{
    case Croatia = 'hr';
    case Iceland = 'is';
    case Norway = 'no';
    case Australia = 'au';
}
