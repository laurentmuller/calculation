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
use App\Interfaces\RoleInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Elao\Enum\FlagBag;

/**
 * Trait to set or get access rights.
 *
 * @phpstan-require-implements RoleInterface
 */
trait RightsTrait
{
    /** The overwritten rights flag. */
    #[ORM\Column(options: ['default' => false])]
    private bool $overwrite = false;

    /** The rights. */
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?int $rights = null;

    /**
     * Gets the permission for the given entity name.
     *
     * @return FlagBag<EntityPermission>
     */
    public function getPermission(EntityName $entity): FlagBag
    {
        $value = $entity->getOffsetValue($this->rights);

        return new FlagBag(EntityPermission::class, $value);
    }

    /**
     * Gets all permissions.
     *
     * @return array<string, FlagBag<EntityPermission>> an array where keys are the form field name
     */
    public function getPermissions(): array
    {
        return \array_reduce(
            EntityName::sorted(),
            fn (array $carry, EntityName $name): array => $carry + [$name->getFormField() => $this->getPermission($name)],
            []
        );
    }

    /**
     * Gets the rights.
     */
    public function getRights(): ?int
    {
        return $this->rights;
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
        $rights = $this->rights ?? 0;
        $rights |= $entity->getShiftedValue($permission);

        return $this->setRights($rights);
    }

    /**
     * Sets the given permission of the given entities.
     *
     * @param FlagBag<EntityPermission> $permission
     */
    public function setPermissions(FlagBag $permission, EntityName ...$entities): static
    {
        foreach ($entities as $entity) {
            $this->setPermission($entity, $permission);
        }

        return $this;
    }

    /**
     * Sets the rights.
     */
    public function setRights(?int $rights): static
    {
        $this->rights = ($rights ?? 0) === 0 ? null : $rights;

        return $this;
    }
}
