<?php

declare(strict_types=1);

namespace Sofascore\PurgatoryBundle\Tests\PHPUnit;

use PHPUnit\Event\Code\Phpt;
use PHPUnit\Event\Code\TestCollection;
use PHPUnit\Event\Code\TestDox;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\TestData\TestDataCollection;
use PHPUnit\Event\TestSuite\TestSuiteForTestClass;
use PHPUnit\Event\TestSuite\TestSuiteWithName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Metadata\MetadataCollection;
use Sofascore\PurgatoryBundle\Listener\EntityChangePurgeSwitcher;
use Sofascore\PurgatoryBundle\PHPUnit\Metadata\AttributeReader;
use Sofascore\PurgatoryBundle\PHPUnit\PurgatoryExtension;
use Sofascore\PurgatoryBundle\Tests\PHPUnit\Fixtures\WithClassAttributeDummy;
use Sofascore\PurgatoryBundle\Tests\PHPUnit\Fixtures\WithMethodAttributeDummy;

#[CoversClass(PurgatoryExtension::class)]
final class PurgatoryExtensionTest extends TestCase
{
    private EntityChangePurgeSwitcher $switcher;

    protected function setUp(): void
    {
        $this->switcher = new EntityChangePurgeSwitcher(false);
    }

    protected function tearDown(): void
    {
        EntityChangePurgeSwitcher::reset();

        unset($this->switcher);
    }

    public function testEnableForTestSuiteWithAttributeOnClass(): void
    {
        PurgatoryExtension::enableForTestSuite(self::testSuite(WithClassAttributeDummy::class), new AttributeReader());

        self::assertTrue($this->switcher->isEnabled());
    }

    public function testEnableForTestSuiteWithoutAttributeOnClass(): void
    {
        PurgatoryExtension::enableForTestSuite(self::testSuite(WithMethodAttributeDummy::class), new AttributeReader());

        self::assertFalse($this->switcher->isEnabled());
    }

    public function testEnableForTestSuiteIgnoresSuitesNotForTestClass(): void
    {
        PurgatoryExtension::enableForTestSuite(new TestSuiteWithName('suite', 0, TestCollection::fromArray([])), new AttributeReader());

        self::assertFalse($this->switcher->isEnabled());
    }

    public function testResetForTestSuiteWithAttributeOnClass(): void
    {
        EntityChangePurgeSwitcher::enable();

        PurgatoryExtension::resetForTestSuite(self::testSuite(WithClassAttributeDummy::class), new AttributeReader());

        self::assertFalse($this->switcher->isEnabled());
    }

    public function testResetForTestSuiteWithoutAttributeOnClass(): void
    {
        EntityChangePurgeSwitcher::enable();

        PurgatoryExtension::resetForTestSuite(self::testSuite(WithMethodAttributeDummy::class), new AttributeReader());

        self::assertTrue($this->switcher->isEnabled());
    }

    public function testEnableForTestWithAttributeOnMethod(): void
    {
        PurgatoryExtension::enableForTest(self::testMethod(WithMethodAttributeDummy::class, 'testWithAttribute'), new AttributeReader());

        self::assertTrue($this->switcher->isEnabled());
    }

    public function testEnableForTestWithoutAttributeOnMethod(): void
    {
        PurgatoryExtension::enableForTest(self::testMethod(WithMethodAttributeDummy::class, 'testWithoutAttribute'), new AttributeReader());

        self::assertFalse($this->switcher->isEnabled());
    }

    public function testEnableForTestIgnoresNonMethodTests(): void
    {
        PurgatoryExtension::enableForTest(new Phpt(__FILE__), new AttributeReader());

        self::assertFalse($this->switcher->isEnabled());
    }

    public function testResetForTestWithAttributeOnMethod(): void
    {
        EntityChangePurgeSwitcher::enable();

        PurgatoryExtension::resetForTest(self::testMethod(WithMethodAttributeDummy::class, 'testWithAttribute'), new AttributeReader());

        self::assertFalse($this->switcher->isEnabled());
    }

    public function testResetForTestWithAttributeOnMethodAndClass(): void
    {
        EntityChangePurgeSwitcher::enable();

        PurgatoryExtension::resetForTest(self::testMethod(WithClassAttributeDummy::class, 'testWithAttribute'), new AttributeReader());

        self::assertTrue($this->switcher->isEnabled());
    }

    public function testResetForTestWithoutAttributeOnMethod(): void
    {
        EntityChangePurgeSwitcher::enable();

        PurgatoryExtension::resetForTest(self::testMethod(WithMethodAttributeDummy::class, 'testWithoutAttribute'), new AttributeReader());

        self::assertTrue($this->switcher->isEnabled());
    }

    public function testResetForTestIgnoresNonMethodTests(): void
    {
        EntityChangePurgeSwitcher::enable();

        PurgatoryExtension::resetForTest(new Phpt(__FILE__), new AttributeReader());

        self::assertTrue($this->switcher->isEnabled());
    }

    public function testResetForTestClassWithoutAttributeOnClass(): void
    {
        EntityChangePurgeSwitcher::enable();

        PurgatoryExtension::resetForTestClass(WithMethodAttributeDummy::class, new AttributeReader());

        self::assertFalse($this->switcher->isEnabled());
    }

    public function testResetForTestClassWithAttributeOnClass(): void
    {
        EntityChangePurgeSwitcher::enable();

        PurgatoryExtension::resetForTestClass(WithClassAttributeDummy::class, new AttributeReader());

        self::assertTrue($this->switcher->isEnabled());
    }

    /**
     * @param class-string $className
     */
    private static function testSuite(string $className): TestSuiteForTestClass
    {
        return new TestSuiteForTestClass($className, 1, TestCollection::fromArray([]), __FILE__, __LINE__);
    }

    /**
     * @param class-string $className
     */
    private static function testMethod(string $className, string $methodName): TestMethod
    {
        return new TestMethod(
            $className,
            $methodName,
            __FILE__,
            __LINE__,
            new TestDox($className, $methodName, $methodName),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );
    }
}
