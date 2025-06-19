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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Elao\Enum\FlagBag;

/**
 * Trait to set or get access rights.
 *
 * @property FlagBag<EntityPermission> $CalculationRights      the permissions for calculations.
 * @property FlagBag<EntityPermission> $CalculationStateRights the permissions for calculation state.
 * @property FlagBag<EntityPermission> $GroupRights            the permissions for groups.
 * @property FlagBag<EntityPermission> $CategoryRights         the permissions for categories.
 * @property FlagBag<EntityPermission> $ProductRights          the permissions for products.
 * @property FlagBag<EntityPermission> $TaskRights             the permissions for tasks.
 * @property FlagBag<EntityPermission> $GlobalMarginRights     the permissions for global margins.
 * @property FlagBag<EntityPermission> $UserRights             the permissions for users.
 * @property FlagBag<EntityPermission> $LogRights              the permissions for logs.
 * @property FlagBag<EntityPermission> $CustomerRights         the permissions for customers.
 */
trait RightsTrait
{
    /**
     * The overwritten rights flag.
     */
    #[ORM\Column(options: ['default' => false])]
    private bool $overwrite = false;

    /**
     * The rights.
     *
     * @var ?int[]
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $rights = null;

    /**
     * NB: The mixed value must be returned. If not, ProxyHelper class will raise an exception.
     * This will no more the case with PHP 8.4.
     *
     * @return FlagBag<EntityPermission>|null
     */
    public function __get(string $name): mixed
    {
        $entity = EntityName::tryFromField($name);

        return $entity instanceof EntityName ? $this->getPermission($entity) : null;
    }

    public function __isset(string $name): bool
    {
        return EntityName::tryFromField($name) instanceof EntityName;
    }

    /**
     * @param FlagBag<EntityPermission>|null $value
     */
    public function __set(string $name, mixed $value): void
    {
        if (!$value instanceof FlagBag) {
            return;
        }
        $entity = EntityName::tryFromField($name);
        if (!$entity instanceof EntityName) {
            return;
        }
        $this->setPermission($entity, $value);
    }

    /**
     * Gets the permission for the given entity name.
     *
     * @return FlagBag<EntityPermission>
     */
    public function getPermission(EntityName $entity): FlagBag
    {
        $offset = $entity->offset();
        $rights = $this->getRights();
        $value = $rights[$offset];

        return new FlagBag(EntityPermission::class, $value);
    }

    /**
     * Gets the rights.
     *
     * @return int[]
     */
    public function getRights(): array
    {
        return $this->rights ?? $this->getEmptyRights();
    }

    /**
     * Gets a value indicating if this right overwrites the default rights.
     *
     * @return bool true if overwritten, false to use the default rights
     */
    public function isOverwrite(): bool
    {
        return $this->overwrite;
    }

    /**
     * Sets a value indicating if this right overwrites the default rights.
     */
    public function setOverwrite(bool $overwrite): static
    {
        $this->overwrite = $overwrite;

        return $this;
    }

    /**
     * Sets the permission for the given entity name.
     *
     * @param FlagBag<EntityPermission> $permission
     */
    public function setPermission(EntityName $entity, FlagBag $permission): static
    {
        $offset = $entity->offset();
        $rights = $this->getRights();
        $rights[$offset] = $permission->getValue();

        return $this->setRights($rights);
    }

    /**
     * Sets the rights.
     *
     * @param int[]|null $rights
     */
    public function setRights(?array $rights): static
    {
        $this->rights = null === $rights || [] === $rights || 0 === \array_sum($rights) ? null : $rights;

        return $this;
    }

    /**
     * @return int[]
     */
    private function getEmptyRights(): array
    {
        $len = \count(EntityName::cases());

        return \array_fill(0, $len, 0);
    }
}
