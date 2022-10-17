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
        // map entity
        if ($subject instanceof EntityName) {
            $subject = $subject->value;
        }

        // map permissions
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
        // check user
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // enabled?
        if (!$user->isEnabled()) {
            return false;
        }

        // super admin can access all
        if ($user->isSuperAdmin()) {
            return true;
        }

        // find entity
        $name = EntityName::tryFindValue($subject);
        if (null === $name) {
            return false;
        }

        // special case for Log entity
        if (EntityName::LOG->match($name)) {
            return $user->isAdmin();
        }

        // get offset
        $offset = EntityName::tryFindOffset($name);
        if (RoleBuilder::INVALID_VALUE === $offset) {
            return false;
        }

        // get mask
        $mask = EntityPermission::tryFindValue($attribute);
        if (RoleBuilder::INVALID_VALUE === $mask) {
            return false;
        }

        // get rights
        if ($user->isOverwrite()) {
            $rights = $user->getRights();
        } elseif ($user->isAdmin()) {
            $rights = $this->service->getAdminRights();
        } else {
            $rights = $this->service->getUserRights();
        }

        // get value
        $value = $rights[$offset];

        // check rights
        return $this->isBitSet($value, $mask);
    }
}
