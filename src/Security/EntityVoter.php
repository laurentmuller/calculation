<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Security;

use App\Entity\Role;
use App\Entity\User;
use App\Interfaces\IEntityVoter;
use App\Service\ApplicationService;
use App\Traits\MathTrait;
use App\Utils\Utils;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter for entities.
 *
 * @author Laurent Muller
 */
class EntityVoter extends Voter implements IEntityVoter
{
    use MathTrait;

    /**
     * The entities list.
     */
    public const ENTITIES = [
        self::ENTITY_CALCULATION,
        self::ENTITY_CALCULATION_STATE,
        self::ENTITY_CATEGORY,
        self::ENTITY_CUSTOMER,
        self::ENTITY_PRODUCT,
        self::ENTITY_GLOBAL_MARGIN,
        self::ENTITY_USER,
    ];

    /**
     * The value returned when attribute mask or entity offset are not found.
     */
    public const INVALID_VALUE = -1;

    /**
     * The attributes bit masks. This attributes are used to read bit set permission.
     */
    public const MASK_ATTRIBUTES = [
        self::ATTRIBUTE_ADD => 1,
        self::ATTRIBUTE_DELETE => 2,
        self::ATTRIBUTE_EDIT => 4,
        self::ATTRIBUTE_LIST => 8,
        self::ATTRIBUTE_PDF => 16,
        self::ATTRIBUTE_SHOW => 32,
    ];

    /**
     * The entities byte offset. This offsets are used to read/write rights.
     */
    public const OFFSETS = [
        self::ENTITY_CALCULATION => 0,
        self::ENTITY_CALCULATION_STATE => 1,
        self::ENTITY_CATEGORY => 2,
        self::ENTITY_CUSTOMER => 3,
        self::ENTITY_PRODUCT => 4,
        self::ENTITY_GLOBAL_MARGIN => 5,
        self::ENTITY_USER => 6,
    ];

    /**
     * Service to get rights.
     *
     * @var ApplicationService
     */
    private $service;

    /**
     * Constructor.
     */
    public function __construct(ApplicationService $service)
    {
        $this->service = $service;
    }

    /**
     * Gets an attribute mask (value) for the given name.
     *
     * @param string $name the attribute name
     *
     * @return int the attribute mask, if found; -1 (IEntityVoter::INVALID_VALUE) otherwise
     */
    public static function getAttributeMask(string $name): int
    {
        return self::MASK_ATTRIBUTES[$name] ?? self::INVALID_VALUE;
    }

    /**
     * Gets an entity offset for the given name.
     *
     * @param string $name the entity name
     *
     * @return int the entity offset, if found; -1 (IEntityVoter::INVALID_VALUE) otherwise
     */
    public static function getEntityOffset(string $name): int
    {
        return self::OFFSETS[$name] ?? self::INVALID_VALUE;
    }

    /**
     * Gets the value at the given offset.
     *
     * @param string $source the string to get value from
     * @param int    $offset the offset to get value for
     *
     * @return int the value or 0 if not found
     */
    public static function getOffsetValue(?string $source, int $offset): int
    {
        $source = Utils::toString($source);
        if (isset($source[$offset])) {
            return \ord($source[$offset]);
        }

        return 0;
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
        $role = new Role(User::ROLE_ADMIN);
        $role->{self::ENTITY_CALCULATION} = $attributes;
        $role->{self::ENTITY_CALCULATION_STATE} = $attributes;
        $role->{self::ENTITY_CATEGORY} = $attributes;
        $role->{self::ENTITY_CUSTOMER} = $attributes;
        $role->{self::ENTITY_PRODUCT} = $attributes;
        $role->{self::ENTITY_GLOBAL_MARGIN} = $attributes;
        $role->{self::ENTITY_USER} = $attributes;

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
        $role = new Role(User::ROLE_SUPER_ADMIN);
        foreach (self::ENTITIES as $entity) {
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
            self::MASK_ATTRIBUTES[self::ATTRIBUTE_PDF],
            self::MASK_ATTRIBUTES[self::ATTRIBUTE_SHOW],
        ];

        $role = new Role(User::ROLE_DEFAULT);
        $role->{self::ENTITY_CALCULATION} = self::MASK_ATTRIBUTES;
        $role->{self::ENTITY_CALCULATION_STATE} = $default;
        $role->{self::ENTITY_CATEGORY} = $default;
        $role->{self::ENTITY_CUSTOMER} = $default;
        $role->{self::ENTITY_PRODUCT} = $default;
        $role->{self::ENTITY_GLOBAL_MARGIN} = $default;
        $role->{self::ENTITY_GLOBAL_MARGIN} = $default;

        return $role;
    }

    /**
     * Sets the value at the given offset.
     *
     * @param string $source the string to update
     * @param int    $offset the offset
     * @param int    $value  the value to set at the offset. Must be between 0 and 255 (inclusive).
     *
     * @return string the updated string
     */
    public static function setOffsetValue(?string $source, int $offset, int $value): string
    {
        // ensure length
        $source = Utils::toString($source);
        $source .= \str_repeat(\chr(0), \max(0, $offset + 1 - \strlen($source)));

        //set value
        $source[$offset] = \chr($value);

        return $source;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        // check attribute
        if (!\array_key_exists($attribute, self::MASK_ATTRIBUTES)) {
            return false;
        }

        // check class name
        $name = $this->getEntityName($subject);
        if (!\array_key_exists($name, self::OFFSETS)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        // check user
        $user = $token->getUser();
        if (!($user instanceof User)) {
            return false;
        }

        // enabled?
        //if (!$user->isEnabled()) {
        //return false;
        //}

        // super admin can access all
        if ($user->hasRole(User::ROLE_SUPER_ADMIN)) {
            return  true;
        }

        // get offset
        $name = $this->getEntityName($subject);
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
        } elseif ($user->hasRole(User::ROLE_ADMIN)) {
            $rights = $this->service->getAdminRights();
        } else {
            $rights = $this->service->getUserRights();
        }

        // get value
        $value = self::getOffsetValue($rights, $offset);

        // check rights
        return $this->isBitSet($value, $mask);
    }

    /**
     * Ensure that the given mask is between 0 and 255 inclusive.
     *
     * @param int $mask the mask to verify
     *
     * @return int the updated mask
     */
    private function checkMask(int $mask): int
    {
        $limit = 2 ** PHP_INT_SIZE;
        while ($mask < 0) {
            $mask += $limit;
        }

        return $mask % $limit;
    }

    /**
     * Gets the entity name for the given subject.
     *
     * @param mixed $subject the subject. Can be an object or a string (class name).
     *
     * @return string the entity name
     */
    private function getEntityName($subject): string
    {
        $name = \is_string($subject) ? (string) $subject : \get_class($subject);
        if (false !== ($pos = \strrpos($name, '\\'))) {
            return \substr($name, $pos + 1);
        }

        return $name;
    }

    /**
     * Checks if the given value contains the bit mask.
     * <p>
     * The value of the mask outside the valid range (0..255) will be bitwise and'ed with 255.
     * </p>.
     *
     * @param int $value  the value to be tested
     * @param int $offset the byte offset
     * @param int $mask   the bit mask
     *
     * @return bool true if set
     */
    private function isOffsetBit(int $value, int $offset, int $mask): bool
    {
        $mask = $this->checkMask($mask);
        $value >>= (PHP_INT_SIZE * $offset);

        return $mask === ($mask & $value);
    }

    /**
     * Update the value by adding given bit mask.
     * <p>
     * The value of the mask outside the valid range (0..255) will be bitwise and'ed with 255.
     * </p>.
     *
     * @param int $value  the value to update
     * @param int $offset the byte offset
     * @param int $mask   the bit mask to add
     *
     * @return int the updated value
     */
    private function setOffsetBit(int $value, int $offset, int $mask): int
    {
        $mask = $this->checkMask($mask);
        $value |= $mask << (PHP_INT_SIZE * $offset);

        return $value;
    }

    /**
     * Update the value by removing all bits at the given offset.
     *
     * @param int $value  the value to update
     * @param int $offset the byte offset
     *
     * @return int the updated value
     */
    private function unsetOffset(int $value, int $offset): int
    {
        $mask = 2 ** PHP_INT_SIZE - 1;

        return $this->unsetOffsetBit($value, $offset, $mask);
    }

    /**
     * Update the value by removing given bit mask.
     * <p>
     * The value of the mask outside the valid range (0..255) will be bitwise and'ed with 255.
     * </p>.
     *
     * @param int $value  the value to update
     * @param int $offset the byte offset
     * @param int $mask   the bit mask to remove
     *
     * @return int the updated value
     */
    private function unsetOffsetBit(int $value, int $offset, int $mask): int
    {
        $mask = $this->checkMask($mask);
        $value &= ~($mask << (PHP_INT_SIZE * $offset));

        return $value;
    }
}
