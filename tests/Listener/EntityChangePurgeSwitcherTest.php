<?php

declare(strict_types=1);

namespace Sofascore\PurgatoryBundle\Tests\Listener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sofascore\PurgatoryBundle\Listener\EntityChangePurgeSwitcher;

#[CoversClass(EntityChangePurgeSwitcher::class)]
final class EntityChangePurgeSwitcherTest extends TestCase
{
    protected function tearDown(): void
    {
        EntityChangePurgeSwitcher::reset();

        parent::tearDown();
    }

    public function testConfiguredDefault(): void
    {
        self::assertTrue((new EntityChangePurgeSwitcher())->isEnabled());
        self::assertTrue((new EntityChangePurgeSwitcher(true))->isEnabled());
        self::assertFalse((new EntityChangePurgeSwitcher(false))->isEnabled());
    }

    public function testOverride(): void
    {
        $enabledByDefault = new EntityChangePurgeSwitcher(true);
        $disabledByDefault = new EntityChangePurgeSwitcher(false);

        EntityChangePurgeSwitcher::enable();

        self::assertTrue($enabledByDefault->isEnabled());
        self::assertTrue($disabledByDefault->isEnabled());

        EntityChangePurgeSwitcher::disable();

        self::assertFalse($enabledByDefault->isEnabled());
        self::assertFalse($disabledByDefault->isEnabled());

        EntityChangePurgeSwitcher::reset();

        self::assertTrue($enabledByDefault->isEnabled());
        self::assertFalse($disabledByDefault->isEnabled());
    }
}
