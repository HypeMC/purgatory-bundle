<?php

declare(strict_types=1);

namespace Sofascore\PurgatoryBundle\Tests\Application;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\RequiresMethod;
use Sofascore\PurgatoryBundle\Attribute\RouteParamValue\PropertyValues;
use Sofascore\PurgatoryBundle\Cache\Configuration\Configuration;
use Sofascore\PurgatoryBundle\Test\InteractsWithPurgatory;
use Sofascore\PurgatoryBundle\Tests\Functional\AbstractKernelTestCase;
use Sofascore\PurgatoryBundle\Tests\Functional\TestApplication\Controller\PersonController;
use Sofascore\PurgatoryBundle\Tests\Functional\TestApplication\Controller\PostController;
use Sofascore\PurgatoryBundle\Tests\Functional\TestApplication\Entity\Person;
use Sofascore\PurgatoryBundle\Tests\Functional\TestApplication\Entity\Post;
use Symfony\Component\HttpKernel\Attribute\Serialize;

#[RequiresMethod(Serialize::class, '__construct')]
final class AutoDetectResponseGroupsTest extends AbstractKernelTestCase
{
    use InteractsWithPurgatory;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::initializeApplication(['test_case' => 'TestApplication', 'config' => 'auto_detect_response_groups.yaml']);

        $this->entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function tearDown(): void
    {
        unset($this->entityManager);

        parent::tearDown();
    }

    /**
     * @see PostController::summaryAction
     */
    public function testResponseGroupsAreUsedWhenTargetIsNotSet(): void
    {
        /** @var Configuration $configuration */
        $configuration = self::getContainer()->get('sofascore.purgatory.configuration_loader')->load();

        $subscription = [
            'routeName' => 'post_summary',
            'routeParams' => [
                'post_id' => [
                    'type' => PropertyValues::type(),
                    'values' => ['id'],
                ],
            ],
        ];

        self::assertContains($subscription, $configuration->get(Post::class.'::title'));
        self::assertNotContains($subscription, $configuration->get(Post::class.'::text'));
        self::assertFalse($configuration->has(Post::class.'::views'));
        self::assertFalse($configuration->has(Post::class));

        $post = new Post();
        $post->title = 'Title';
        $post->text = 'Text';

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        $summaryUrl = '/post/'.$post->id.'/summary';

        self::assertUrlIsPurged($summaryUrl);

        self::clearPurger();

        $post->views = 10;
        $this->entityManager->flush();

        self::assertUrlIsNotPurged($summaryUrl);

        $post->text = 'New text';
        $this->entityManager->flush();

        self::assertUrlIsNotPurged($summaryUrl);

        $post->title = 'New title';
        $this->entityManager->flush();

        self::assertUrlIsPurged($summaryUrl);
    }

    /**
     * @see PersonController::detailsAction
     */
    public function testAllPropertiesAreUsedWithoutSerializeAttribute(): void
    {
        $person = new Person();
        $person->firstName = 'John';
        $person->lastName = 'Doe';
        $person->gender = 'male';

        $this->entityManager->persist($person);
        $this->entityManager->flush();

        self::clearPurger();

        $person->gender = 'female';
        $this->entityManager->flush();

        self::assertUrlIsPurged('/person/'.$person->id);
    }
}
