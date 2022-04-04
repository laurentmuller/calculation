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

namespace App\Security;

use App\Entity\User;
use App\Interfaces\EntityVoterInterface;
use App\Interfaces\RoleInterface;
use App\Model\Role;
use App\Service\ApplicationService;
use App\Traits\MathTrait;
use App\Util\Utils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for entities.
 *
 * @author Laurent Muller
 */
class EntityVoter extends Voter implements EntityVoterInterface
{
    use MathTrait;

    /**
     * The entities offset. These offsets are used to read/write rights.
     */
    final public const ENTITY_OFFSETS = [
        self::ENTITY_CALCULATION => 0,
        self::ENTITY_CALCULATION_STATE => 1,
        self::ENTITY_GROUP => 2,
        self::ENTITY_CATEGORY => 3,
        self::ENTITY_CUSTOMER => 4,
        self::ENTITY_PRODUCT => 5,
        self::ENTITY_GLOBAL_MARGIN => 6,
        self::ENTITY_USER => 7,
        self::ENTITY_TASK => 8,
    ];

    /**
     * The value returned when attribute mask or entity offset is not found.
     */
    final public const INVALID_VALUE = -1;

    /**
     * The attributes bit masks. These attributes are used to read or write bit set permissions.
     */
    final public const MASK_ATTRIBUTES = [
        self::ATTRIBUTE_ADD => 1,
        self::ATTRIBUTE_DELETE => 2,
        self::ATTRIBUTE_EDIT => 4,
        self::ATTRIBUTE_LIST => 8,
        self::ATTRIBUTE_EXPORT => 16,
        self::ATTRIBUTE_SHOW => 32,
    ];

    /**
     * The entity prefix.
     */
    private const ENTITY_PREFIX = 'Entity';

    /**
     * Constructor.
     */
    public function __construct(private readonly ApplicationService $service)
    {
    }

    /**
     * Gets an attribute mask (value) for the given name.
     *
     * @param string $name the attribute name
     *
     * @return int the attribute mask, if found; -1 (EntityVoterInterface::INVALID_VALUE) otherwise
     */
    public static function getAttributeMask(string $name): int
    {
        return self::MASK_ATTRIBUTES[$name] ?? self::INVALID_VALUE;
    }

    /**
     * Gets the empty rights.
     *
     * @return int[]
     */
    public static function getEmptyRights(): array
    {
        return \array_fill(0, \count(self::ENTITY_OFFSETS), 0);
    }

    /**
     * Gets the entity name for the given subject.
     *
     * @param mixed $subject the subject. Can be an object or a string (class name).
     *
     * @return string the entity name
     */
    public static function getEntityName(mixed $subject): string
    {
        if (\is_string($subject)) {
            $name = $subject;
        } elseif (\is_object($subject)) {
            $name = $subject::class;
        } else {
            $name = (string) $subject;
        }
        if (false !== ($pos = \strrpos($name, '\\'))) {
            $name = \substr($name, $pos + 1);
        }

        if (!Utils::startwith($name, self::ENTITY_PREFIX)) {
            return self::ENTITY_PREFIX . $name;
        }

        return $name;
    }

    /**
     * Gets an entity offset for the given name.
     *
     * @param string $name the entity name
     *
     * @return int the entity offset, if found; -1 (EntityVoterInterface::INVALID_VALUE) otherwise
     */
    public static function getEntityOffset(string $name): int
    {
        return self::ENTITY_OFFSETS[$name] ?? self::INVALID_VALUE;
    }

    /**
     * Gets a role with default rights for the given user.
     *
     * @param User $user the user to get role for
     *
     * @return Role the role with the default rights
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
     * Gets the default rights for the admin role ('ROLE_ADMIN').
     *
     * @return Role the role with the default rights
     */
    public static function getRoleAdmin(): Role
    {
        $attributes = self::MASK_ATTRIBUTES;
        $role = new Role(RoleInterface::ROLE_ADMIN);
        $entities = \array_keys(self::ENTITY_OFFSETS);
        foreach ($entities as $entity) {
            $role->{$entity} = $attributes;
        }

        return $role;
    }

    /**
     * Gets the default rights for a disabled user.
     *
     * @return Role the role with the default rights
     */
    public static function getRoleDisabled(): Role
    {
        $role = new Role(RoleInterface::ROLE_USER);
        $role->setOverwrite(true);

        return $role;
    }

    /**
     * Gets the default rights for the super admin role ('ROLE_SUPER_ADMIN').
     *
     * @return Role the role with the default rights
     */
    public static function getRoleSuperAdmin(): Role
    {
        $attributes = self::MASK_ATTRIBUTES;
        $role = new Role(RoleInterface::ROLE_SUPER_ADMIN);
        $entities = \array_keys(self::ENTITY_OFFSETS);
        foreach ($entities as $entity) {
            $role->{$entity} = $attributes;
        }

        return $role;
    }

    /**
     * Gets the default rights for the user role ('ROLE_USER').
     *
     * @return Role the role with the default rights
     */
    public static function getRoleUser(): Role
    {
        // default attributes for all except calculation
        $default = [
            self::MASK_ATTRIBUTES[self::ATTRIBUTE_LIST],
            self::MASK_ATTRIBUTES[self::ATTRIBUTE_EXPORT],
            self::MASK_ATTRIBUTES[self::ATTRIBUTE_SHOW],
        ];

        $role = new Role(RoleInterface::ROLE_USER);
        $role->{self::ENTITY_CALCULATION} = self::MASK_ATTRIBUTES;
        $role->{self::ENTITY_CALCULATION_STATE} = $default;
        $role->{self::ENTITY_GROUP} = $default;
        $role->{self::ENTITY_CATEGORY} = $default;
        $role->{self::ENTITY_CUSTOMER} = $default;
        $role->{self::ENTITY_PRODUCT} = $default;
        $role->{self::ENTITY_GLOBAL_MARGIN} = $default;
        $role->{self::ENTITY_TASK} = $default;
        $role->{self::ENTITY_USER} = [];

        return $role;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsAttribute(string $attribute): bool
    {
        return \array_key_exists($attribute, self::MASK_ATTRIBUTES);
    }

    /**
     * {@inheritdoc}
     *
     * @see Voter
     */
    protected function supports($attribute, $subject): bool
    {
        // check attribute
        if (!\array_key_exists($attribute, self::MASK_ATTRIBUTES)) {
            return false;
        }

        // check entity name
        $name = self::getEntityName($subject);
        if (self::ENTITY_LOG === $name) {
            return true;
        }

        return \array_key_exists($name, self::ENTITY_OFFSETS);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $subject
     *
     * @see Voter
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        // check user
        $user = $token->getUser();
        if (!($user instanceof User)) {
            return false;
        }

        // enabled?
        if (!$user->isEnabled()) {
            return false;
        }

        // super admin can access all
        $roles = $token->getRoleNames();
        if (\in_array(RoleInterface::ROLE_SUPER_ADMIN, $roles, true)) {
            return true;
        }

        // special case for Log entity
        $name = self::getEntityName($subject);
        if (self::ENTITY_LOG === $name) {
            return true;
        }

        // get offset
        $offset = self::getEntityOffset($name);
        if (self::INVALID_VALUE === $offset) {
            return false;
        }

        // get mask
        $mask = self::getAttributeMask($attribute);
        if (self::INVALID_VALUE === $mask) {
            return false;
        }

        // get rights
        if ($user->isOverwrite()) {
            $rights = $user->getRights();
        } elseif (\in_array(RoleInterface::ROLE_ADMIN, $roles, true)) {
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
