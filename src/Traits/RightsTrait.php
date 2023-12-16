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
use Doctrine\ORM\Mapping as ORM;
use Elao\Enum\FlagBag;

/**
 * Trait to set or get access rights.
 */
trait RightsTrait
{
    use MathTrait;

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
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::JSON, nullable: true)]
    private ?array $rights = null;

    /**
     * @return FlagBag<EntityPermission>
     */
    public function getCalculationPermission(): FlagBag
    {
        return $this->getPermission(EntityName::CALCULATION);
    }

    /**
     * @return FlagBag<EntityPermission>
     */
    public function getCalculationStatePermission(): FlagBag
    {
        return $this->getPermission(EntityName::CALCULATION_STATE);
    }

    /**
     * @return FlagBag<EntityPermission>
     */
    public function getCategoryPermission(): FlagBag
    {
        return $this->getPermission(EntityName::CATEGORY);
    }

    /**
     * @return FlagBag<EntityPermission>
     */
    public function getCustomerPermission(): FlagBag
    {
        return $this->getPermission(EntityName::CUSTOMER);
    }

    /**
     * @return FlagBag<EntityPermission>
     */
    public function getGlobalMarginPermission(): FlagBag
    {
        return $this->getPermission(EntityName::GLOBAL_MARGIN);
    }

    /**
     * @return FlagBag<EntityPermission>
     */
    public function getGroupPermission(): FlagBag
    {
        return $this->getPermission(EntityName::GROUP);
    }

    /**
     * @return FlagBag<EntityPermission>
     */
    public function getLogPermission(): FlagBag
    {
        return $this->getPermission(EntityName::LOG);
    }

    /**
     * @return FlagBag<EntityPermission>
     */
    public function getProductPermission(): FlagBag
    {
        return $this->getPermission(EntityName::PRODUCT);
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
     * @return FlagBag<EntityPermission>
     */
    public function getTaskPermission(): FlagBag
    {
        return $this->getPermission(EntityName::TASK);
    }

    /**
     * @return FlagBag<EntityPermission>
     */
    public function getUserPermission(): FlagBag
    {
        return $this->getPermission(EntityName::USER);
    }

    /**
     * Gets a value indicating if this rights overwrite the default rights.
     *
     * @return bool true if overwritten, false to use the default rights
     */
    public function isOverwrite(): bool
    {
        return $this->overwrite;
    }

    /**
     * @param FlagBag<EntityPermission> $permission
     */
    public function setCalculationPermission(FlagBag $permission): static
    {
        return $this->setPermission(EntityName::CALCULATION, $permission);
    }

    /**
     * @param FlagBag<EntityPermission> $permission
     */
    public function setCalculationStatePermission(FlagBag $permission): static
    {
        return $this->setPermission(EntityName::CALCULATION_STATE, $permission);
    }

    /**
     * @param FlagBag<EntityPermission> $permission
     */
    public function setCategoryPermission(FlagBag $permission): static
    {
        return $this->setPermission(EntityName::CATEGORY, $permission);
    }

    /**
     * @param FlagBag<EntityPermission> $permission
     */
    public function setCustomerPermission(FlagBag $permission): static
    {
        return $this->setPermission(EntityName::CUSTOMER, $permission);
    }

    /**
     * @param FlagBag<EntityPermission> $permission
     */
    public function setGlobalMarginPermission(FlagBag $permission): static
    {
        return $this->setPermission(EntityName::GLOBAL_MARGIN, $permission);
    }

    /**
     * @param FlagBag<EntityPermission> $permission
     */
    public function setGroupPermission(FlagBag $permission): static
    {
        return $this->setPermission(EntityName::GROUP, $permission);
    }

    /**
     * @param FlagBag<EntityPermission> $permission
     */
    public function setLogPermission(FlagBag $permission): static
    {
        return $this->setPermission(EntityName::LOG, $permission);
    }

    /**
     * Sets a value indicating if this rights overwrite the default rights.
     */
    public function setOverwrite(bool $overwrite): static
    {
        $this->overwrite = $overwrite;

        return $this;
    }

    /**
     * @param FlagBag<EntityPermission> $permission
     */
    public function setProductPermission(FlagBag $permission): static
    {
        return $this->setPermission(EntityName::PRODUCT, $permission);
    }

    /**
     * Sets the rights.
     *
     * @psalm-param int[]|null $rights
     */
    public function setRights(?array $rights): static
    {
        $this->rights = null === $rights || [] === $rights || 0 === \array_sum($rights) ? null : $rights;

        return $this;
    }

    /**
     * @param FlagBag<EntityPermission> $permission
     */
    public function setTaskPermission(FlagBag $permission): static
    {
        return $this->setPermission(EntityName::TASK, $permission);
    }

    /**
     * @param FlagBag<EntityPermission> $permission
     */
    public function setUserPermission(FlagBag $permission): static
    {
        return $this->setPermission(EntityName::USER, $permission);
    }

    /**
     * Gets the empty rights.
     *
     * @return int[]
     */
    private function getEmptyRights(): array
    {
        $len = \count(EntityName::cases());

        return \array_fill(0, $len, 0);
    }

    /**
     * Gets the permission for the given entity name.
     *
     * @return FlagBag<EntityPermission>
     */
    private function getPermission(EntityName $entity): FlagBag
    {
        $offset = $entity->offset();
        $rights = $this->getRights();
        $value = $rights[$offset];

        return new FlagBag(EntityPermission::class, $value);
    }

    /**
     * Sets the permission for the given entity name.
     *
     * @param FlagBag<EntityPermission> $permission
     */
    private function setPermission(EntityName $entity, FlagBag $permission): static
    {
        $offset = $entity->offset();
        $rights = $this->getRights();
        $rights[$offset] = $permission->getValue();

        return $this->setRights($rights);
    }
}
