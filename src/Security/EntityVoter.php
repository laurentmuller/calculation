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

namespace App\Security;

use App\Entity\User;
use App\Enums\EntityName;
use App\Enums\EntityPermission;
use App\Interfaces\RoleInterface;
use App\Service\ApplicationService;
use App\Traits\MathTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for entities.
 *
 * @phpstan-extends Voter<string, EntityName|string>
 */
class EntityVoter extends Voter
{
    use MathTrait;

    /**
     * Constructor.
     */
    public function __construct(private readonly ApplicationService $service)
    {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return EntityPermission::tryFromName($attribute) instanceof EntityPermission;
    }

    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        if ($subject instanceof EntityName) {
            $subject = $subject->value;
        }
        $attributes = \array_map(static fn (mixed $value): mixed => ($value instanceof EntityPermission) ? $value->name : $value, $attributes);

        return parent::vote($token, $subject, $attributes);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->supportsAttribute($attribute) && EntityName::tryFromMixed($subject) instanceof EntityName;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!($user = $this->getUser($token)) instanceof User) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        if (null === $name = EntityName::tryFindValue($subject)) {
            return false;
        }
        if (EntityName::LOG->matchValue($name)) {
            return $user->isAdmin();
        }
        if (RoleInterface::INVALID_VALUE === $offset = EntityName::tryFindOffset($name)) {
            return false;
        }
        if (RoleInterface::INVALID_VALUE === $mask = EntityPermission::tryFindValue($attribute)) {
            return false;
        }
        $rights = $this->getRights($user);
        $value = $rights[$offset];

        return $this->isBitSet($value, $mask);
    }

    /**
     * @psalm-return int[]
     */
    private function getRights(User $user): array
    {
        if ($user->isOverwrite()) {
            return $user->getRights();
        }
        if ($user->isAdmin()) {
            return $this->service->getAdminRights();
        }

        return $this->service->getUserRights();
    }

    private function getUser(TokenInterface $token): ?User
    {
        // check user
        $user = $token->getUser();
        if (!$user instanceof User || !$user->isEnabled()) {
            return null;
        }

        return $user;
    }
}
