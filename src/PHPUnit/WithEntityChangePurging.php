<?php

declare(strict_types=1);

namespace Sofascore\PurgatoryBundle\PHPUnit;

/**
 * Enables purging on entity changes for a test class or a single test method
 * when the "entity_change_purging" option is disabled. Requires the
 * PurgatoryExtension to be registered in the PHPUnit configuration.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class WithEntityChangePurging
{
}
