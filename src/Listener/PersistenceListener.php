<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
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
use App\Traits\TranslatorFlashMessageTrait;
use App\Util\Utils;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Entity modifications listener.
 *
 * @author Laurent Muller
 */
class PersistenceListener implements EventSubscriber
{
    use TranslatorFlashMessageTrait;

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
     * The debug mode.
     */
    private bool $debug;

    /**
     * The message title.
     */
    private string $title;

    /**
     * Constructor.
     */
    public function __construct(RequestStack $requestStack, TranslatorInterface $translator, KernelInterface $kernel, private string $appName)
    {
        $this->debug = $kernel->isDebug();
        $this->setTranslator($translator);
        $this->setRequestStack($requestStack);
        $id = \sprintf('environment.%s', $kernel->getEnvironment());
        $this->title = $this->trans($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents(): array
    {
        if ($this->debug) {
            return [
                Events::postUpdate,
                Events::postPersist,
                Events::postRemove,
            ];
        } else {
            return [];
        }
    }

    /**
     * Handles the post persist event.
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        if (null !== ($entity = $this->getEntity($args))) {
            $id = $this->getId($entity, '.add.success');
            $params = $this->getParameters($entity);
            $this->info($this->translateMessage($id, $params));
        }
    }

    /**
     * Handles the post remove event.
     */
    public function postRemove(LifecycleEventArgs $args): void
    {
        if (null !== ($entity = $this->getEntity($args))) {
            $id = $this->getId($entity, '.delete.success');
            $params = $this->getParameters($entity);
            $this->warning($this->translateMessage($id, $params));
        }
    }

    /**
     * Handles the post update event.
     */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        if (null !== ($entity = $this->getEntity($args))) {
            // special case for user entity when last login change
            if ($entity instanceof User && $this->isLastLogin($args, $entity)) {
                $id = 'security.login.success';
                $params = [
                    '%username%' => $entity->getUsername(),
                    '%appname%' => $this->appName,
                ];
            } else {
                $id = $this->getId($entity, '.edit.success');
                $params = $this->getParameters($entity);
            }
            $this->info($this->translateMessage($id, $params));
        }
    }

    /**
     * Gets the entity from the given arguments.
     *
     * @param LifecycleEventArgs $args the arguments to get entity for
     *
     * @return AbstractEntity|null the entity, if found; null otherwise
     */
    private function getEntity(LifecycleEventArgs $args): ?AbstractEntity
    {
        /** @var AbstractEntity $entity */
        $entity = $args->getObject();
        if (\in_array(\get_class($entity), self::CLASS_NAMES, true)) {
            return $entity;
        }

        return null;
    }

    /**
     * Gets the message identifier to translate.
     *
     * @param AbstractEntity $entity the entity
     * @param string         $suffix the message suffix
     *
     * @return string the message identifier to translate
     */
    private function getId(AbstractEntity $entity, string $suffix): string
    {
        $name = \strtolower(Utils::getShortName($entity));

        return $name . $suffix;
    }

    /**
     * Gets the message parameters.
     *
     * @param AbstractEntity $entity the entity
     *
     * @return array the message parameters
     */
    private function getParameters(AbstractEntity $entity): array
    {
        return ['%name%' => $entity->getDisplay()];
    }

    /**
     * Checks if the last login field is updated.
     *
     * @param LifecycleEventArgs $args the post update arguments
     * @param User               $user the user entity
     *
     * @return bool true if updated
     */
    private function isLastLogin(LifecycleEventArgs $args, User $user): bool
    {
        $manager = $args->getEntityManager();
        $unitOfWork = $manager->getUnitOfWork();
        $changeSet = $unitOfWork->getEntityChangeSet($user);

        return \array_key_exists('lastLogin', $changeSet);
    }

    /**
     * Translates the given message and add the title as prefix.
     *
     * @param string      $id         the message id
     * @param array       $parameters an array of parameters for the message
     * @param string|null $domain     the domain for the message or null to use the default
     *
     * @return string the translated string
     */
    private function translateMessage(string $id, array $parameters = [], ?string $domain = null): string
    {
        $message = $this->trans($id, $parameters, $domain);

        return $this->title . '|' . $message;
    }
}
