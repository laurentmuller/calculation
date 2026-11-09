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
use App\Parameter\ApplicationParameters;
use App\Traits\MathTrait;
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

    public function __construct(private readonly ApplicationParameters $parameters)
    {
    }

    #[\Override]
    public function vote(TokenInterface $token, mixed $subject, array $attributes, ?Vote $vote = null): int
    {
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
        return EntityPermission::tryFromName($attribute) instanceof EntityPermission
            && EntityName::tryFromMixed($subject) instanceof EntityName;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $this->getUser($token, $vote);
        if (!$user instanceof User) {
            return false;
        }
        if ($user->isWebmaster()) {
            return true;
        }

        $entityName = EntityName::fromMixed($subject);
        $entityPermission = EntityPermission::fromName($attribute);
        if ($this->isAllowed($user, $entityName, $entityPermission)) {
            $this->addReason(
                $vote,
                'The user has the %s permission on %s.',
                $entityPermission->name,
                $entityName->name
            );

            return true;
        }

        $this->addReason(
            $vote,
            'The user does not have the %s permission on %s.',
            $entityPermission->name,
            $entityName->name
        );

        return false;
    }

    private function addReason(?Vote $vote, string $reason, string ...$parameters): null
    {
        $vote?->addReason(\sprintf($reason, ...$parameters));

        return null;
    }

    private function getRights(User $user): int
    {
        if ($user->overwrite) {
            return $user->rights ?? 0;
        }

        $rights = $this->parameters->getRights();
        if ($user->isAdmin()) {
            return $rights->getAdminRole()->rights ?? 0;
        }

        return $rights->getUserRole()->rights ?? 0;
    }

    private function getUser(TokenInterface $token, ?Vote $vote): ?User
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return $this->addReason($vote, 'The authentication token does not contains an instance of User.');
        }
        if (!$user->enabled) {
            return $this->addReason($vote, 'The logged in user is disabled.');
        }

        return $user;
    }

    private function isAllowed(User $user, EntityName $entityName, EntityPermission $entityPermission): bool
    {
        $rights = $this->getRights($user);
        $value = $entityName->getOffsetValue($rights);
        $mask = $entityPermission->value;

        return $this->isBitSet($value, $mask);
    }
}
