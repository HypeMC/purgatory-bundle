<?php

declare(strict_types=1);

namespace Sofascore\PurgatoryBundle\PHPUnit;

use PHPUnit\Event\Code\Test;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\BeforeTestMethodErrored;
use PHPUnit\Event\Test\BeforeTestMethodErroredSubscriber;
use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\Test\SkippedSubscriber;
use PHPUnit\Event\TestSuite\Finished as TestSuiteFinished;
use PHPUnit\Event\TestSuite\FinishedSubscriber as TestSuiteFinishedSubscriber;
use PHPUnit\Event\TestSuite\Started as TestSuiteStarted;
use PHPUnit\Event\TestSuite\StartedSubscriber as TestSuiteStartedSubscriber;
use PHPUnit\Event\TestSuite\TestSuite;
use PHPUnit\Event\TestSuite\TestSuiteForTestClass;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use Sofascore\PurgatoryBundle\Listener\EntityChangePurgeSwitcher;
use Sofascore\PurgatoryBundle\PHPUnit\Metadata\AttributeReader;

/**
 * Enables purging on entity changes for test classes and test methods
 * marked with the "#[WithEntityChangePurging]" attribute.
 */
final class PurgatoryExtension implements Extension
{
    /**
     * @codeCoverageIgnore
     */
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $reader = new AttributeReader();

        $facade->registerSubscriber(new class($reader) implements TestSuiteStartedSubscriber {
            public function __construct(
                private readonly AttributeReader $reader,
            ) {
            }

            public function notify(TestSuiteStarted $event): void
            {
                PurgatoryExtension::enableForTestSuite($event->testSuite(), $this->reader);
            }
        });

        $facade->registerSubscriber(new class($reader) implements TestSuiteFinishedSubscriber {
            public function __construct(
                private readonly AttributeReader $reader,
            ) {
            }

            public function notify(TestSuiteFinished $event): void
            {
                PurgatoryExtension::resetForTestSuite($event->testSuite(), $this->reader);
            }
        });

        $facade->registerSubscriber(new class($reader) implements PreparationStartedSubscriber {
            public function __construct(
                private readonly AttributeReader $reader,
            ) {
            }

            public function notify(PreparationStarted $event): void
            {
                PurgatoryExtension::enableForTest($event->test(), $this->reader);
            }
        });

        $facade->registerSubscriber(new class($reader) implements FinishedSubscriber {
            public function __construct(
                private readonly AttributeReader $reader,
            ) {
            }

            public function notify(Finished $event): void
            {
                PurgatoryExtension::resetForTest($event->test(), $this->reader);
            }
        });

        // the "Finished" event is not emitted when a test errors or is skipped before it is prepared
        $facade->registerSubscriber(new class($reader) implements ErroredSubscriber {
            public function __construct(
                private readonly AttributeReader $reader,
            ) {
            }

            public function notify(Errored $event): void
            {
                PurgatoryExtension::resetForTest($event->test(), $this->reader);
            }
        });

        $facade->registerSubscriber(new class($reader) implements SkippedSubscriber {
            public function __construct(
                private readonly AttributeReader $reader,
            ) {
            }

            public function notify(Skipped $event): void
            {
                PurgatoryExtension::resetForTest($event->test(), $this->reader);
            }
        });

        if (interface_exists(BeforeTestMethodErroredSubscriber::class)) {
            $facade->registerSubscriber(new class($reader) implements BeforeTestMethodErroredSubscriber {
                public function __construct(
                    private readonly AttributeReader $reader,
                ) {
                }

                public function notify(BeforeTestMethodErrored $event): void
                {
                    PurgatoryExtension::resetForTestClass($event->testClassName(), $this->reader);
                }
            });
        }
    }

    /**
     * @internal
     */
    public static function enableForTestSuite(TestSuite $testSuite, AttributeReader $reader): void
    {
        if ($testSuite instanceof TestSuiteForTestClass && null !== $reader->forClass($testSuite->className())) {
            EntityChangePurgeSwitcher::enable();
        }
    }

    /**
     * @internal
     */
    public static function resetForTestSuite(TestSuite $testSuite, AttributeReader $reader): void
    {
        if ($testSuite instanceof TestSuiteForTestClass && null !== $reader->forClass($testSuite->className())) {
            EntityChangePurgeSwitcher::reset();
        }
    }

    /**
     * @internal
     */
    public static function enableForTest(Test $test, AttributeReader $reader): void
    {
        if ($test instanceof TestMethod && null !== $reader->forMethod($test->className(), $test->methodName())) {
            EntityChangePurgeSwitcher::enable();
        }
    }

    /**
     * @internal
     */
    public static function resetForTest(Test $test, AttributeReader $reader): void
    {
        if (!$test instanceof TestMethod || null === $reader->forMethod($test->className(), $test->methodName())) {
            return;
        }

        self::resetForTestClass($test->className(), $reader);
    }

    /**
     * Restores the configured default unless the attribute is set on the class,
     * in which case purging stays enabled until the test suite is finished.
     *
     * @internal
     *
     * @param class-string $className
     */
    public static function resetForTestClass(string $className, AttributeReader $reader): void
    {
        if (null === $reader->forClass($className)) {
            EntityChangePurgeSwitcher::reset();
        }
    }
}
