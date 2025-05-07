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
use App\Service\ApplicationService;
use App\Traits\MathTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for entities.
 *
 * @extends Voter<string, EntityName|string>
 */
class EntityVoter extends Voter
{
    use MathTrait;

    public function __construct(private readonly ApplicationService $service)
    {
    }

    #[\Override]
    public function supportsAttribute(string $attribute): bool
    {
        return EntityPermission::tryFromName($attribute) instanceof EntityPermission;
    }

    #[\Override]
    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        /** @phpstan-var mixed $attribute */
        foreach ($attributes as &$attribute) {
            if ($attribute instanceof EntityPermission) {
                $attribute = $attribute->name;
            }
        }

        return parent::vote($token, $subject, $attributes);
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->supportsAttribute($attribute) && EntityName::tryFromMixed($subject) instanceof EntityName;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $this->getUser($token);
        if (!$user instanceof User) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }

        $permission = EntityPermission::tryFromName($attribute);
        if (!$permission instanceof EntityPermission) {
            return false;
        }
        $name = EntityName::tryFromMixed($subject);
        if (!$name instanceof EntityName) {
            return false;
        }

        $rights = $this->getRights($user);
        $offset = $name->offset();
        $value = $rights[$offset];
        $mask = $permission->value;

        return $this->isBitSet($value, $mask);
    }

    /**
     * @return int[]
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
        $user = $token->getUser();

        return $user instanceof User && $user->isEnabled() ? $user : null;
    }
}
