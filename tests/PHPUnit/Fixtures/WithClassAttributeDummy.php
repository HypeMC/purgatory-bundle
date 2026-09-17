<?php

declare(strict_types=1);

namespace Sofascore\PurgatoryBundle\Tests\PHPUnit\Fixtures;

use Sofascore\PurgatoryBundle\PHPUnit\WithEntityChangePurging;

#[WithEntityChangePurging]
class WithClassAttributeDummy
{
    public function testWithoutAttribute(): void
    {
    }

    #[WithEntityChangePurging]
    public function testWithAttribute(): void
    {
    }
}
