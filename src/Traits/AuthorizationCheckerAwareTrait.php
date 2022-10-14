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
 * Trait to check grant actions.
 */
trait AuthorizationCheckerAwareTrait
{
    private ?AuthorizationCheckerInterface $authorizationChecker = null;

    /** @var bool[] */
    private array $rights = [];

    #[SubscribedService]
    public function getAuthorizationChecker(): AuthorizationCheckerInterface
    {
        if (null === $this->authorizationChecker) {
            /** @psalm-var AuthorizationCheckerInterface $result */
            $result = $this->container->get(__CLASS__ . '::' . __FUNCTION__);
            $this->authorizationChecker = $result;
        }

        return $this->authorizationChecker;
    }

    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker): static
    {
        $this->authorizationChecker = $authorizationChecker;

        return $this;
    }

    /**
     * Returns if the given action for the given subject (entity name) is granted.
     */
    protected function isGranted(string|EntityPermission $action, string|EntityName $subject): bool
    {
        $key = \sprintf('%s.%s', $this->asString($action), $this->asString($subject));
        if (!isset($this->rights[$key])) {
            return $this->rights[$key] = $this->getAuthorizationChecker()->isGranted($action, $subject);
        }

        return $this->rights[$key];
    }

    /**
     * Returns if the given subject (entity name) can be added.
     */
    protected function isGrantedAdd(string $subject): bool
    {
        return $this->isGranted(EntityPermission::ADD, $subject);
    }

    /**
     * Returns if the given subject (entity name) can be deleted.
     */
    protected function isGrantedDelete(string $subject): bool
    {
        return $this->isGranted(EntityPermission::DELETE, $subject);
    }

    /**
     * Returns if the given subject (entity name) can be edited.
     */
    protected function isGrantedEdit(string $subject): bool
    {
        return $this->isGranted(EntityPermission::EDIT, $subject);
    }

    /**
     * Returns if the given subject (entity name) can be exported.
     */
    protected function isGrantedExport(string $subject): bool
    {
        return $this->isGranted(EntityPermission::EXPORT, $subject);
    }

    /**
     * Returns if the given list of subjects (entity name) can be displayed.
     */
    protected function isGrantedList(string $subject): bool
    {
        return $this->isGranted(EntityPermission::LIST, $subject);
    }

    /**
     * Returns if the given subject (entity name) can be displayed.
     */
    protected function isGrantedShow(string $subject): bool
    {
        return $this->isGranted(EntityPermission::SHOW, $subject);
    }

    private function asString(string|\UnitEnum $value): string
    {
        return $value instanceof \UnitEnum ? $value->name : $value;
    }
}
