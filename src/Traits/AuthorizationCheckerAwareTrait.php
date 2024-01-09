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

namespace App\Traits;

use App\Enums\EntityName;
use App\Enums\EntityPermission;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Service\Attribute\SubscribedService;

/**
 * Trait to check grant actions within the subscribed service.
 *
 * @psalm-require-implements \Symfony\Contracts\Service\ServiceSubscriberInterface
 */
trait AuthorizationCheckerAwareTrait
{
    use AwareTrait;

    private ?AuthorizationCheckerInterface $checker = null;

    /** @psalm-var array<string, bool> */
    private array $rights = [];

    #[SubscribedService]
    public function getChecker(): AuthorizationCheckerInterface
    {
        if ($this->checker instanceof AuthorizationCheckerInterface) {
            return $this->checker;
        }

        return $this->checker = $this->getContainerService(__FUNCTION__, AuthorizationCheckerInterface::class);
    }

    public function setChecker(AuthorizationCheckerInterface $checker): static
    {
        $this->checker = $checker;

        return $this;
    }

    /**
     * Returns if the given action for the given subject is granted.
     */
    protected function isGranted(EntityPermission|string $action, EntityName|string $subject): bool
    {
        $key = $this->getGrantedKey($action, $subject);
        if (isset($this->rights[$key])) {
            return $this->rights[$key];
        }

        return $this->rights[$key] = $this->getChecker()->isGranted($action, $subject);
    }

    /**
     * Returns if the given subject can be added.
     */
    protected function isGrantedAdd(EntityName|string $subject): bool
    {
        return $this->isGranted(EntityPermission::ADD, $subject);
    }

    /**
     * Returns if the given subject can be deleted.
     */
    protected function isGrantedDelete(EntityName|string $subject): bool
    {
        return $this->isGranted(EntityPermission::DELETE, $subject);
    }

    /**
     * Returns if the given subject can be edited.
     */
    protected function isGrantedEdit(EntityName|string $subject): bool
    {
        return $this->isGranted(EntityPermission::EDIT, $subject);
    }

    /**
     * Returns if the given list od subjects can be exported.
     */
    protected function isGrantedExport(EntityName|string $subject): bool
    {
        return $this->isGranted(EntityPermission::EXPORT, $subject);
    }

    /**
     * Returns if the given list of subjects can be displayed.
     */
    protected function isGrantedList(EntityName|string $subject): bool
    {
        return $this->isGranted(EntityPermission::LIST, $subject);
    }

    /**
     * Returns if the given subject can be displayed.
     */
    protected function isGrantedShow(EntityName|string $subject): bool
    {
        return $this->isGranted(EntityPermission::SHOW, $subject);
    }

    private function getGrantedKey(EntityPermission|string $action, EntityName|string $subject): string
    {
        if ($action instanceof EntityPermission) {
            $action = $action->name;
        }
        if ($subject instanceof EntityName) {
            $subject = $subject->name;
        }

        return \sprintf('%s.%s', $action, $subject);
    }
}
