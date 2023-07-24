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

namespace App\Listener;

use App\Entity\AbstractEntity;
use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\GlobalMargin;
use App\Entity\Group;
use App\Entity\Product;
use App\Entity\Task;
use App\Entity\User;
use App\Enums\FlashType;
use App\Interfaces\DisableListenerInterface;
use App\Traits\DisableListenerTrait;
use App\Traits\TranslatorFlashMessageAwareTrait;
use App\Utils\StringUtils;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Listener to add flash messages when entities are created, updated or deleted.
 */
#[AsDoctrineListener(Events::postPersist)]
#[AsDoctrineListener(Events::postRemove)]
#[AsDoctrineListener(Events::postUpdate)]
#[AsDoctrineListener(Events::preRemove)]
class PersistenceListener implements DisableListenerInterface, ServiceSubscriberInterface
{
    use DisableListenerTrait;
    use ServiceSubscriberTrait;
    use TranslatorFlashMessageAwareTrait;

    /**
     * The entity class names to listen for.
     */
    private const CLASS_NAMES = [
        Calculation::class,
        CalculationState::class,
        Category::class,
        Customer::class,
        GlobalMargin::class,
        Group::class,
        Product::class,
        Task::class,
        User::class,
    ];

    private ?string $previousDisplay = null;

    /**
     * @psalm-param LifecycleEventArgs<\Doctrine\ORM\EntityManagerInterface> $args
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $this->getEntity($args);
        if (!$entity instanceof AbstractEntity) {
            return;
        }
        $this->notify($entity, '.add.success', 'common.add_success');
    }

    /**
     * @psalm-param LifecycleEventArgs<\Doctrine\ORM\EntityManagerInterface> $args
     */
    public function postRemove(LifecycleEventArgs $args): void
    {
        $entity = $this->getEntity($args);
        if (!$entity instanceof AbstractEntity) {
            return;
        }
        $this->notify($entity, '.delete.success', 'common.delete_success', FlashType::WARNING);
    }

    /**
     * @psalm-param LifecycleEventArgs<\Doctrine\ORM\EntityManagerInterface> $args
     */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $this->getEntity($args);
        if (!$entity instanceof AbstractEntity || $this->isUserLastLogin($args)) {
            return;
        }
        if ($this->isUserRights($args)) {
            $this->notify($entity, '', 'user.rights.success');
        } elseif ($this->isUserPassword($args)) {
            $this->notify($entity, '', 'user.change_password.change_success');
        } else {
            $this->notify($entity, '.edit.success', 'common.edit_success');
        }
    }

    /**
     * @psalm-param LifecycleEventArgs<\Doctrine\ORM\EntityManagerInterface> $args
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        $this->previousDisplay = null;
        $entity = $this->getEntity($args);
        if (!$entity instanceof Calculation) {
            return;
        }
        $this->previousDisplay = $entity->getDisplay();
    }

    /**
     * @psalm-param LifecycleEventArgs<\Doctrine\ORM\EntityManagerInterface> $args
     */
    private function getEntity(LifecycleEventArgs $args): ?AbstractEntity
    {
        if (!$this->isEnabled()) {
            return null;
        }

        /** @var AbstractEntity $entity */
        $entity = $args->getObject();
        if (\in_array($entity::class, self::CLASS_NAMES, true)) {
            return $entity;
        }

        return null;
    }

    private function getId(AbstractEntity $entity, string $suffix, string $default): string
    {
        $id = \strtolower(StringUtils::getShortName($entity)) . $suffix;

        return $this->isTransDefined($id) ? $id : $default;
    }

    /**
     * @psalm-template T of AbstractEntity
     *
     * @psalm-param LifecycleEventArgs<\Doctrine\ORM\EntityManagerInterface> $args
     * @psalm-param class-string<T> $class
     */
    private function isObjectChange(LifecycleEventArgs $args, string $class, string ...$fields): bool
    {
        $object = $args->getObject();
        if ($object::class !== $class) {
            return false;
        }
        $manager = $args->getObjectManager();
        $unitOfWork = $manager->getUnitOfWork();
        $changeSet = $unitOfWork->getEntityChangeSet($object);
        foreach ($fields as $field) {
            if (\array_key_exists($field, $changeSet)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @psalm-param LifecycleEventArgs<\Doctrine\ORM\EntityManagerInterface> $args
     */
    private function isUserLastLogin(LifecycleEventArgs $args): bool
    {
        return $this->isObjectChange($args, User::class, 'lastLogin');
    }

    /**
     * @psalm-param LifecycleEventArgs<\Doctrine\ORM\EntityManagerInterface> $args
     */
    private function isUserPassword(LifecycleEventArgs $args): bool
    {
        return $this->isObjectChange($args, User::class, 'password');
    }

    /**
     * @psalm-param LifecycleEventArgs<\Doctrine\ORM\EntityManagerInterface> $args
     */
    private function isUserRights(LifecycleEventArgs $args): bool
    {
        return $this->isObjectChange($args, User::class, 'rights', 'overwrite');
    }

    private function notify(AbstractEntity $entity, string $suffix, string $default, FlashType $type = FlashType::SUCCESS): void
    {
        $id = $this->getId($entity, $suffix, $default);
        $display = $this->previousDisplay ?? $entity->getDisplay();
        $message = $this->trans($id, ['%name%' => $display]);
        $this->addFlashMessage($type, $message);
        $this->previousDisplay = null;
    }
}
