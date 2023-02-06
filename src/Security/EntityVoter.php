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
use App\Util\RoleBuilder;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for entities.
 *
 * @extends Voter<string, EntityName|string>
 *
 * @psalm-suppress TooManyTemplateParams
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

    /**
     * {@inheritDoc}
     */
    public function supportsAttribute(string $attribute): bool
    {
        return null !== EntityPermission::tryFromName($attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        // map subject
        if ($subject instanceof EntityName) {
            $subject = $subject->value;
        }

        // map attributes
        $attributes = \array_map(static fn (mixed $value): mixed => ($value instanceof EntityPermission) ? $value->name : $value, $attributes);

        return parent::vote($token, $subject, $attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->supportsAttribute($attribute) && null !== EntityName::tryFromMixed($subject);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        // get user
        if (null === $user = $this->getUser($token)) {
            return false;
        }

        // super admin can access all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // find entity
        if (null === $name = EntityName::tryFindValue($subject)) {
            return false;
        }

        // special case for Log entity
        if (EntityName::LOG->matchValue($name)) {
            return $user->isAdmin();
        }

        // get offset
        if (RoleBuilder::INVALID_VALUE === $offset = EntityName::tryFindOffset($name)) {
            return false;
        }

        // get mask
        if (RoleBuilder::INVALID_VALUE === $mask = EntityPermission::tryFindValue($attribute)) {
            return false;
        }

        // get rights
        $rights = $this->getRights($user);

        // get value
        $value = $rights[$offset];

        // check rights
        return $this->isBitSet($value, $mask);
    }

    /**
     * @psalm-return int[]
     *
     * @throws \Psr\Cache\InvalidArgumentException
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
