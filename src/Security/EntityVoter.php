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
use App\Utils\StringUtils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
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
    public function vote(TokenInterface $token, mixed $subject, array $attributes, ?Vote $vote = null): int
    {
        /** @phpstan-var mixed $attribute */
        foreach ($attributes as &$attribute) {
            if ($attribute instanceof EntityPermission) {
                $attribute = $attribute->name;
            }
        }

        return parent::vote($token, $subject, $attributes, $vote);
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->supportsAttribute($attribute) && EntityName::tryFromMixed($subject) instanceof EntityName;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $this->getUser($token, $vote);
        if (!$user instanceof User) {
            return false;
        }
        if ($this->isSuperAdmin($user, $vote)) {
            return true;
        }
        $entityPermission = $this->getEntityPermission($attribute, $vote);
        if (!$entityPermission instanceof EntityPermission) {
            return false;
        }
        $entityName = $this->getEntityName($subject, $vote);
        if (!$entityName instanceof EntityName) {
            return false;
        }

        $result = $this->isAllowed($user, $entityName, $entityPermission);
        if ($result) {
            $this->addReason(
                $vote,
                'User "%s" has the "%s" permission on "%s".',
                $user->getUsername(),
                $entityPermission->name,
                $entityName->name
            );
        } else {
            $this->addReason(
                $vote,
                'User "%s" does not have the "%s" permission on "%s".',
                $user->getUsername(),
                $entityPermission->name,
                $entityName->name
            );
        }

        return $result;
    }

    private function addReason(?Vote $vote, string $reason, string ...$parameters): null
    {
        $vote?->addReason(\sprintf($reason, ...$parameters));

        return null;
    }

    private function getEntityName(mixed $subject, ?Vote $vote): ?EntityName
    {
        $name = EntityName::tryFromMixed($subject);
        if (!$name instanceof EntityName) {
            return $this->addReason(
                $vote,
                'Subject "%s" is not a valid entity name.',
                StringUtils::getDebugType($subject)
            );
        }

        return $name;
    }

    private function getEntityPermission(string $attribute, ?Vote $vote = null): ?EntityPermission
    {
        $permission = EntityPermission::tryFromName($attribute);
        if (!$permission instanceof EntityPermission) {
            return $this->addReason($vote, 'Attribute "%s" is not a valid entity permission.', $attribute);
        }

        return $permission;
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

    private function getUser(TokenInterface $token, ?Vote $vote): ?User
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return $this->addReason($vote, 'Token does not contains an instance of User.');
        }
        if (!$user->isEnabled()) {
            return $this->addReason($vote, 'User "%s" is disabled.', $user->getUsername());
        }

        return $user;
    }

    private function isAllowed(User $user, EntityName $entityName, EntityPermission $entityPermission): bool
    {
        $rights = $this->getRights($user);
        $value = $rights[$entityName->offset()];
        $mask = $entityPermission->value;

        return $this->isBitSet($value, $mask);
    }

    private function isSuperAdmin(User $user, ?Vote $vote): bool
    {
        if ($user->isSuperAdmin()) {
            $this->addReason(
                $vote,
                'User "%s" has "%s" role.',
                $user->getUsername(),
                RoleInterface::ROLE_SUPER_ADMIN
            );

            return true;
        }

        return false;
    }
}
