<?php

declare(strict_types=1);

namespace Sofascore\PurgatoryBundle\Tests\PHPUnit\Fixtures;

use Sofascore\PurgatoryBundle\PHPUnit\WithEntityChangePurging;

final class WithMethodAttributeDummy
{
    public function testWithoutAttribute(): void
    {
    }

    #[WithEntityChangePurging]
    public function testWithAttribute(): void
    {
    }
}
