<?php

/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Listener;

use App\Entity\Log;
use App\Entity\Task;
use App\Entity\TaskItem;
use App\Entity\User;
use App\Interfaces\EntityInterface;
use App\Listener\PersistenceListener;
use App\Tests\TranslatorMockTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class PersistenceListenerTest extends TestCase
{
    use TranslatorMockTrait;

    private User $user;

    #[\Override]
    protected function setUp(): void
    {
        $this->user = new User();
        $this->user->setUsername('user_name');
    }

    public function testCollectionDeletions(): void
    {
        $task = new Task();
        $taskItem = new TaskItem();
        $task->addItem($taskItem);
        $event = $this->createEvent(
            ['getScheduledEntityDeletions' => $task],
            ['getScheduledCollectionDeletions' => $taskItem]
        );

        $listener = $this->createListener();
        $listener->onFlush($event);
        self::assertTrue($listener->isEnabled());
    }

    public function testCollectionUpdates(): void
    {
        $task = new Task();
        $taskItem = new TaskItem();
        $task->addItem($taskItem);
        $event = $this->createEvent(
            ['getScheduledEntityDeletions' => $task],
            ['getScheduledCollectionUpdates' => $taskItem]
        );

        $listener = $this->createListener();
        $listener->onFlush($event);
        self::assertTrue($listener->isEnabled());
    }

    public function testDelete(): void
    {
        $task = new Task();
        $task->setName('Task');
        $event = $this->createEvent(['getScheduledEntityDeletions' => $task]);

        $listener = $this->createListener();
        $listener->onFlush($event);
        self::assertTrue($listener->isEnabled());
    }

    public function testDeleteDiff(): void
    {
        $task = new Task();
        $taskItem = new TaskItem();
        $task->addItem($taskItem);
        $event = $this->createEvent([
            'getScheduledEntityDeletions' => $task,
            'getScheduledEntityUpdates' => $taskItem,
        ]);

        $listener = $this->createListener();
        $listener->onFlush($event);
        self::assertTrue($listener->isEnabled());
    }

    public function testDisabled(): void
    {
        $event = $this->createEvent();

        $listener = $this->createListener();
        $listener->setEnabled(false);
        $listener->onFlush($event);
        self::assertFalse($listener->isEnabled());
    }

    public function testInsert(): void
    {
        $task = new Task();
        $task->setName('Task');
        $event = $this->createEvent(['getScheduledEntityInsertions' => $task]);

        $listener = $this->createListener();
        $listener->onFlush($event);
        self::assertTrue($listener->isEnabled());
    }

    public function testIsCurrentUser(): void
    {
        $event = $this->createEvent(['getScheduledEntityUpdates' => $this->user]);

        $listener = $this->createListener();
        $listener->onFlush($event);
        self::assertTrue($listener->isEnabled());
    }

    public function testNotTimestampable(): void
    {
        $log = new Log();
        $event = $this->createEvent(['getScheduledEntityUpdates' => $log]);

        $listener = $this->createListener();
        $listener->onFlush($event);
        self::assertTrue($listener->isEnabled());
    }

    public function testUpdate(): void
    {
        $task = new Task();
        $task->setName('Task');
        $event = $this->createEvent(['getScheduledEntityUpdates' => $task]);

        $listener = $this->createListener();
        $listener->onFlush($event);
        self::assertTrue($listener->isEnabled());
    }

    public function testUpdateUserLastLogin(): void
    {
        $user = new User();
        $user->setUsername('user_name');
        $event = $this->createEvent(
            ['getScheduledEntityUpdates' => $user],
            [],
            ['lastLogin' => 'lastLogin']
        );

        $listener = $this->createListener();
        $listener->onFlush($event);
        self::assertTrue($listener->isEnabled());
    }

    public function testUpdateUserPassword(): void
    {
        $user = new User();
        $user->setUsername('user_name');
        $event = $this->createEvent(
            ['getScheduledEntityUpdates' => $user],
            [],
            ['password' => 'password']
        );

        $listener = $this->createListener();
        $listener->onFlush($event);
        self::assertTrue($listener->isEnabled());
    }

    public function testUpdateUserReset(): void
    {
        $user = new User();
        $user->setUsername('user_name');
        $event = $this->createEvent(
            ['getScheduledEntityUpdates' => $user],
            [],
            ['selector' => 'selector']
        );

        $listener = $this->createListener();
        $listener->onFlush($event);
        self::assertTrue($listener->isEnabled());
    }

    public function testUpdateUserRights(): void
    {
        $user = new User();
        $user->setUsername('user_name');
        $event = $this->createEvent(
            ['getScheduledEntityUpdates' => $user],
            [],
            ['rights' => 'rights']
        );

        $listener = $this->createListener();
        $listener->onFlush($event);
        self::assertTrue($listener->isEnabled());
    }

    public function testUserNotChange(): void
    {
        $user = new User();
        $user->setUsername('fake');
        $event = $this->createEvent(['getScheduledEntityUpdates' => $user]);

        $listener = $this->createListener();
        $listener->onFlush($event);
        self::assertTrue($listener->isEnabled());
    }

    /**
     * @phpstan-param array<string, EntityInterface> $events
     * @phpstan-param array<string, EntityInterface> $collections
     * @phpstan-param array<string, string>  $changeSets
     */
    private function createEvent(
        array $events = [],
        array $collections = [],
        array $changeSets = [],
    ): OnFlushEventArgs {
        $objectManager = $this->createMockObjectManager($events, $collections, $changeSets);

        return new OnFlushEventArgs($objectManager);
    }

    private function createListener(): PersistenceListener
    {
        $security = $this->createMockSecurity();
        $listener = new PersistenceListener($security);
        $listener->setTranslator($this->createMockTranslator());
        $listener->setRequestStack($this->createRequestStack());

        return $listener;
    }

    /**
     * @phpstan-param array<string, EntityInterface> $events
     * @phpstan-param array<string, EntityInterface> $collections
     * @phpstan-param array<string, string>  $changeSets
     */
    private function createMockObjectManager(
        array $events = [],
        array $collections = [],
        array $changeSets = []
    ): MockObject&EntityManagerInterface {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        foreach ($events as $method => $entity) {
            $unitOfWork->method($method)
                ->willReturn([$entity]);
        }
        $index = 0;
        foreach ($collections as $method => $entity) {
            $unitOfWork->method($method)
                ->willReturn([$index++ => new ArrayCollection([$entity])]);
        }

        foreach ($changeSets as $key => $value) {
            $unitOfWork->method('getEntityChangeSet')
                ->willReturn([$key => $value]);
        }

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        return $manager;
    }

    private function createMockSecurity(): MockObject&Security
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')
            ->willReturn($this->user);

        return $security;
    }

    private function createRequestStack(): MockObject&RequestStack
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')
            ->willReturn(self::createStub(SessionInterface::class));

        return $requestStack;
    }
}
