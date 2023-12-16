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
     * @return FlagBag<EntityPermission>|null
     */
    public function getCalculationPermission(): ?FlagBag
    {
        return $this->getEntityRights(EntityName::CALCULATION);
    }

    /**
     * @return FlagBag<EntityPermission>|null
     */
    public function getCalculationStatePermission(): ?FlagBag
    {
        return $this->getEntityRights(EntityName::CALCULATION_STATE);
    }

    /**
     * @return FlagBag<EntityPermission>|null
     */
    public function getCategoryPermission(): ?FlagBag
    {
        return $this->getEntityRights(EntityName::CATEGORY);
    }

    /**
     * @return FlagBag<EntityPermission>|null
     */
    public function getCustomerPermission(): ?FlagBag
    {
        return $this->getEntityRights(EntityName::CUSTOMER);
    }

    /**
     * @return FlagBag<EntityPermission>|null
     */
    public function getGlobalMarginPermission(): ?FlagBag
    {
        return $this->getEntityRights(EntityName::GLOBAL_MARGIN);
    }

    /**
     * @return FlagBag<EntityPermission>|null
     */
    public function getGroupPermission(): ?FlagBag
    {
        return $this->getEntityRights(EntityName::GROUP);
    }

    /**
     * @return FlagBag<EntityPermission>|null
     */
    public function getLogPermission(): ?FlagBag
    {
        return $this->getEntityRights(EntityName::LOG);
    }

    /**
     * @return FlagBag<EntityPermission>|null
     */
    public function getProductPermission(): ?FlagBag
    {
        return $this->getEntityRights(EntityName::PRODUCT);
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
     * @return FlagBag<EntityPermission>|null
     */
    public function getTaskPermission(): ?FlagBag
    {
        return $this->getEntityRights(EntityName::TASK);
    }

    /**
     * @return FlagBag<EntityPermission>|null
     */
    public function getUserPermission(): ?FlagBag
    {
        return $this->getEntityRights(EntityName::USER);
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
     * @param FlagBag<EntityPermission>|null $rights
     */
    public function setCalculationPermission(?FlagBag $rights): static
    {
        return $this->setEntityRights(EntityName::CALCULATION, $rights);
    }

    /**
     * @param FlagBag<EntityPermission>|null $rights
     */
    public function setCalculationStatePermission(?FlagBag $rights): static
    {
        return $this->setEntityRights(EntityName::CALCULATION_STATE, $rights);
    }

    /**
     * @param FlagBag<EntityPermission>|null $rights
     */
    public function setCategoryPermission(?FlagBag $rights): static
    {
        return $this->setEntityRights(EntityName::CATEGORY, $rights);
    }

    /**
     * @param FlagBag<EntityPermission>|null $rights
     */
    public function setCustomerPermission(?FlagBag $rights): static
    {
        return $this->setEntityRights(EntityName::CUSTOMER, $rights);
    }

    /**
     * @param FlagBag<EntityPermission>|null $rights
     */
    public function setGlobalMarginPermission(?FlagBag $rights): static
    {
        return $this->setEntityRights(EntityName::GLOBAL_MARGIN, $rights);
    }

    /**
     * @param FlagBag<EntityPermission>|null $rights
     */
    public function setGroupPermission(?FlagBag $rights): static
    {
        return $this->setEntityRights(EntityName::GROUP, $rights);
    }

    /**
     * @param FlagBag<EntityPermission>|null $rights
     */
    public function setLogPermission(?FlagBag $rights): static
    {
        return $this->setEntityRights(EntityName::LOG, $rights);
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
     * @param FlagBag<EntityPermission>|null $rights
     */
    public function setProductPermission(?FlagBag $rights): static
    {
        return $this->setEntityRights(EntityName::PRODUCT, $rights);
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
     * @param FlagBag<EntityPermission>|null $rights
     */
    public function setTaskPermission(?FlagBag $rights): static
    {
        return $this->setEntityRights(EntityName::TASK, $rights);
    }

    /**
     * @param FlagBag<EntityPermission>|null $rights
     */
    public function setUserPermission(?FlagBag $rights): static
    {
        return $this->setEntityRights(EntityName::USER, $rights);
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
     * Gets the rights for the given entity name.
     *
     * @return FlagBag<EntityPermission>|null
     */
    private function getEntityRights(EntityName $entity): ?FlagBag
    {
        $offset = EntityName::tryFindOffset($entity);
        if (EntityName::INVALID_VALUE === $offset) {
            return null;
        }
        $rights = $this->getRights();
        $value = $rights[$offset] ?? 0;

        return new FlagBag(EntityPermission::class, $value);
    }

    /**
     * Sets the rights for the given entity.
     *
     * @param FlagBag<EntityPermission>|null $rights
     */
    private function setEntityRights(EntityName $entity, ?FlagBag $rights): static
    {
        $offset = EntityName::tryFindOffset($entity);
        if (EntityName::INVALID_VALUE !== $offset) {
            $newRights = $this->getRights();
            $newRights[$offset] = $rights?->getValue() ?? 0;

            return $this->setRights($newRights);
        }

        return $this;
    }
}
