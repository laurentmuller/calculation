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
     * Gets all permissions.
     *
     * @return array<string, FlagBag<EntityPermission>> an array where keys are the form field name
     */
    public function getPermissions(): array
    {
        return \array_reduce(
            EntityName::sorted(),
            /** @param array<string, FlagBag<EntityPermission>> $carry */
            fn (array $carry, EntityName $name) => $carry + [$name->getFormField() => $this->getPermission($name)],
            []
        );
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
        $this->rights = 0 === \array_sum($rights ?? []) ? null : $rights;

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
