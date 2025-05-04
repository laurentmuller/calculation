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
use App\Interfaces\TimestampableInterface;
use App\Listener\TimestampableListener;
use App\Tests\TranslatorMockTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class TimestampableListenerTest extends TestCase
{
    use TranslatorMockTrait;

    private const USER_NAME = 'user_name';

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
        self::assertNull($task->getCreatedBy());
    }

    public function testDisabled(): void
    {
        $event = $this->createEvent();

        $listener = $this->createListener();
        $listener->setEnabled(false);
        $listener->onFlush($event);
        self::assertFalse($listener->isEnabled());
    }

    public function testEmptyUser(): void
    {
        $task = new Task();
        $event = $this->createEvent(['getScheduledEntityUpdates' => $task]);

        $listener = $this->createListener(false);
        $listener->onFlush($event);
        self::assertEntityUpdated($task, 'common.entity_empty_user');
    }

    public function testNoEntity(): void
    {
        $event = $this->createEvent();

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
        $event = $this->createEvent(['getScheduledEntityUpdates' => $task]);

        $listener = $this->createListener();
        $listener->onFlush($event);
        self::assertEntityUpdated($task);
    }

    public function testUpdateWithChild(): void
    {
        $task = new Task();
        $taskItem = new TaskItem();
        $task->addItem($taskItem);
        $event = $this->createEvent(['getScheduledEntityUpdates' => $taskItem]);

        $listener = $this->createListener();
        $listener->onFlush($event);
        self::assertEntityUpdated($task);
    }

    protected static function assertEntityUpdated(
        TimestampableInterface $entity,
        string $userName = self::USER_NAME
    ): void {
        self::assertNotNull($entity->getCreatedAt());
        self::assertNotNull($entity->getCreatedBy());
        self::assertNotNull($entity->getUpdatedAt());
        self::assertNotNull($entity->getUpdatedBy());
        self::assertSame($userName, $entity->getCreatedBy());
        self::assertSame($userName, $entity->getUpdatedBy());
    }

    /**
     * @phpstan-param array<string, EntityInterface> $events
     */
    private function createEvent(array $events = []): OnFlushEventArgs
    {
        $objectManager = $this->createMockObjectManager($events);

        return new OnFlushEventArgs($objectManager);
    }

    private function createListener(bool $createUser = true): TimestampableListener
    {
        $security = $this->createMockSecurity($createUser);
        $translator = $this->createMockTranslator();

        return new TimestampableListener($security, $translator);
    }

    /**
     * @phpstan-param array<string, EntityInterface> $events
     */
    private function createMockObjectManager(array $events = []): MockObject&EntityManagerInterface
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        foreach ($events as $method => $entity) {
            $unitOfWork->method($method)
                ->willReturn([$entity]);
        }

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getUnitOfWork')
            ->willReturn($unitOfWork);

        return $manager;
    }

    private function createMockSecurity(bool $createUser = true): MockObject&Security
    {
        $security = $this->createMock(Security::class);
        if ($createUser) {
            $user = new User();
            $user->setUsername(self::USER_NAME);
            $security->method('getUser')
                ->willReturn($user);
        }

        return $security;
    }
}
