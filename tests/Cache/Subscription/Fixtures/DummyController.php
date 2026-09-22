<?php

declare(strict_types=1);

namespace Sofascore\PurgatoryBundle\Tests\Cache\Subscription\Fixtures;

use Symfony\Component\HttpKernel\Attribute\Serialize;

class DummyController
{
    public function barAction(): void
    {
    }

    #[Serialize(context: ['groups' => 'group1'])]
    public function serializeWithGroupsAction(): void
    {
    }

    #[Serialize]
    public function serializeWithoutGroupsAction(): void
    {
    }
}
