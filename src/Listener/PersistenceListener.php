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
        if (!$entity instanceof AbstractEntity || $this->isLastLogin($args)) {
            return;
        }

        $this->notify($entity, '.edit.success', 'common.edit_success');
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
     * @psalm-param LifecycleEventArgs<\Doctrine\ORM\EntityManagerInterface> $args
     */
    private function isLastLogin(LifecycleEventArgs $args): bool
    {
        if ($args->getObject() instanceof User) {
            $manager = $args->getObjectManager();
            $unitOfWork = $manager->getUnitOfWork();
            $changeSet = $unitOfWork->getEntityChangeSet($args->getObject());

            return \array_key_exists('lastLogin', $changeSet);
        }

        return false;
    }

    private function notify(AbstractEntity $entity, string $suffix, string $default, FlashType $type = FlashType::SUCCESS): void
    {
        $id = $this->getId($entity, $suffix, $default);
        $params = ['%name%' => $entity->getDisplay()];
        $message = $this->trans($id, $params);
        $this->addFlashMessage($type, $message);
    }
}
