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
use App\Model\Role;
use App\Service\ApplicationService;
use App\Traits\MathTrait;
use Elao\Enum\FlagBag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for entities.
 */
class EntityVoter extends Voter
{
    use MathTrait;

    /**
     * The value returned when attribute or entity offset is not found.
     */
    final public const INVALID_VALUE = -1;

    /**
     * Constructor.
     */
    public function __construct(private readonly ApplicationService $service)
    {
    }

    /**
     * Gets the empty rights.
     *
     * @return int[]
     */
    public static function getEmptyRights(): array
    {
        $len = \count(EntityName::cases());

        return \array_fill(0, $len, 0);
    }

    /**
     * Find the entity name for the given subject.
     */
    public static function getEntityName(mixed $subject): ?string
    {
        $entity = EntityName::tryFromMixed($subject);

        return $entity?->value;
    }

    /**
     * Gets the offset for the given entity name.
     *
     * @param string $name the entity name to find
     *
     * @return int the entity offset, if found; -1 otherwise
     */
    public static function getEntityOffset(string $name): int
    {
        $entity = EntityName::tryFromMixed($name);

        return $entity?->offset() ?? self::INVALID_VALUE;
    }

    /**
     * Gets permission value for the given name.
     *
     * @param string $name the permission name to find
     *
     * @return int the permission value, if found; -1 otherwise
     */
    public static function getPermissionValue(string $name): int
    {
        $permission = EntityPermission::tryFromName($name);

        return $permission instanceof EntityPermission ? $permission->value : self::INVALID_VALUE;
    }

    /**
     * Gets a role with default rights for the given user.
     */
    public static function getRole(User $user): Role
    {
        if (!$user->isEnabled()) {
            return self::getRoleDisabled();
        }
        if ($user->isSuperAdmin()) {
            return self::getRoleSuperAdmin();
        }
        if ($user->isAdmin()) {
            return self::getRoleAdmin();
        }

        return self::getRoleUser();
    }

    /**
     * Gets the admin role ('ROLE_ADMIN') with default rights.
     */
    public static function getRoleAdmin(): Role
    {
        return self::getRoleWithAll(RoleInterface::ROLE_ADMIN);
    }

    /**
     * Gets disabled role with the default rights.
     */
    public static function getRoleDisabled(): Role
    {
        $role = new Role(RoleInterface::ROLE_USER);
        $role->setOverwrite(true);

        return $role;
    }

    /**
     * Gets the super admin role ('ROLE_SUPER_ADMIN') with default rights.
     */
    public static function getRoleSuperAdmin(): Role
    {
        return self::getRoleWithAll(RoleInterface::ROLE_SUPER_ADMIN);
    }

    /**
     * Gets the user role ('ROLE_USER') with the default rights.
     *
     * @psalm-suppress InvalidArgument
     */
    public static function getRoleUser(): Role
    {
        /** @var FlagBag<EntityPermission> $default */
        $default = FlagBag::from(
            EntityPermission::LIST,
            EntityPermission::EXPORT,
            EntityPermission::SHOW
        );
        $none = new FlagBag(EntityPermission::class, FlagBag::NONE);

        $role = new Role(RoleInterface::ROLE_USER);
        $role->EntityCalculation = self::getFlagBagSorted();
        $role->EntityCalculationState = $default;
        $role->EntityCategory = $default;
        $role->EntityCustomer = $default;
        $role->EntityGlobalMargin = $default;
        $role->EntityGroup = $default;
        $role->EntityLog = $none;
        $role->EntityProduct = $default;
        $role->EntityTask = $default;
        $role->EntityUser = $none;

        return $role;
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
        // map entity name to value
        if ($subject instanceof EntityName) {
            $subject = $subject->value;
        }

        // map permissions to names
        $attributes = \array_map(static fn (mixed $value): mixed => ($value instanceof EntityPermission) ? $value->name : $value, $attributes);

        return parent::vote($token, $subject, $attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        // check attribute
        if (!$this->supportsAttribute($attribute)) {
            return false;
        }

        // check entity name
        return null !== EntityName::tryFromMixed($subject);
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
        $name = self::getEntityName($subject);
        if (null === $name) {
            return false;
        }

        // special case for Log entity
        if (EntityName::LOG->match($name)) {
            return true;
        }

        // get offset
        $offset = self::getEntityOffset($name);
        if (self::INVALID_VALUE === $offset) {
            return false;
        }

        // get mask
        $mask = self::getPermissionValue($attribute);
        if (self::INVALID_VALUE === $mask) {
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

    /**
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     * @psalm-suppress InvalidArgument
     *
     * @return FlagBag<EntityPermission>
     */
    private static function getFlagBagSorted(): FlagBag
    {
        return FlagBag::from(...EntityPermission::sorted());
    }

    private static function getRoleWithAll(string $roleName): Role
    {
        $role = new Role($roleName);
        $value = self::getFlagBagSorted();
        $entities = EntityName::constants();
        foreach ($entities as $entity) {
            $role->$entity = $value;
        }

        return $role;
    }
}
