<?php

declare(strict_types=1);

namespace Sofascore\PurgatoryBundle\PHPUnit\Metadata;

use Sofascore\PurgatoryBundle\PHPUnit\WithEntityChangePurging;

/**
 * @internal
 */
final class AttributeReader
{
    /**
     * @var array<string, ?WithEntityChangePurging>
     */
    private array $cache = [];

    /**
     * Also looks at the parent classes.
     *
     * @param class-string $className
     */
    public function forClass(string $className): ?WithEntityChangePurging
    {
        if (\array_key_exists($className, $this->cache)) {
            return $this->cache[$className];
        }

        $attribute = null;
        for ($class = new \ReflectionClass($className); false !== $class; $class = $class->getParentClass()) {
            if (null !== $attribute = $this->readAttribute($class)) {
                break;
            }
        }

        return $this->cache[$className] = $attribute;
    }

    /**
     * @param class-string $className
     */
    public function forMethod(string $className, string $methodName): ?WithEntityChangePurging
    {
        $key = $className.'::'.$methodName;

        if (\array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        return $this->cache[$key] = $this->readAttribute(new \ReflectionMethod($className, $methodName));
    }

    /**
     * @param \ReflectionClass<object>|\ReflectionMethod $reflection
     */
    private function readAttribute(\ReflectionClass|\ReflectionMethod $reflection): ?WithEntityChangePurging
    {
        return ($reflection->getAttributes(WithEntityChangePurging::class)[0] ?? null)?->newInstance();
    }
}
