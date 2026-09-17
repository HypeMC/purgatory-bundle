<?php

declare(strict_types=1);

namespace Sofascore\PurgatoryBundle\Listener;

/**
 * Controls whether entity changes trigger purge requests.
 *
 * The configured value is the default. It can be overridden globally at
 * runtime using the static methods, e.g. by the PHPUnit extension.
 */
final class EntityChangePurgeSwitcher
{
    private static ?bool $override = null;

    public function __construct(
        private readonly bool $enabled = true,
    ) {
    }

    public function isEnabled(): bool
    {
        return self::$override ?? $this->enabled;
    }

    public static function enable(): void
    {
        self::$override = true;
    }

    public static function disable(): void
    {
        self::$override = false;
    }

    /**
     * Restores the configured default.
     */
    public static function reset(): void
    {
        self::$override = null;
    }
}
