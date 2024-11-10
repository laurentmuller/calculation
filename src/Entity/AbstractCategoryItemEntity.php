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

namespace App\Entity;

use App\Utils\StringUtils;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Abstract entity with a category, a unit and a supplier.
 */
#[ORM\MappedSuperclass]
abstract class AbstractCategoryItemEntity extends AbstractEntity
{
    /**
     * The parent's category.
     */
    protected ?Category $category = null;

    /**
     * The supplier.
     */
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    private ?string $supplier = null;

    /**
     * The unit.
     */
    #[Assert\Length(max: 15)]
    #[ORM\Column(length: 15, nullable: true)]
    private ?string $unit = null;

    /**
     * Get category.
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * Gets the category code.
     */
    public function getCategoryCode(): string
    {
        return $this->getCategory()?->getCode() ?? '';
    }

    /**
     * Gets the category identifier.
     */
    public function getCategoryId(): ?int
    {
        return $this->getCategory()?->getId();
    }

    /**
     * Gets the group.
     */
    public function getGroup(): ?Group
    {
        return $this->getCategory()?->getGroup();
    }

    /**
     * Gets the group code.
     */
    public function getGroupCode(): string
    {
        return $this->getGroup()?->getCode() ?? '';
    }

    /**
     * Gets the supplier.
     */
    public function getSupplier(): ?string
    {
        return $this->supplier;
    }

    /**
     * Get unit.
     */
    public function getUnit(): ?string
    {
        return $this->unit;
    }

    /**
     * Set category.
     */
    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Sets the supplier.
     */
    public function setSupplier(?string $supplier): static
    {
        $this->supplier = StringUtils::trim($supplier);

        return $this;
    }

    /**
     * Set unit.
     */
    public function setUnit(?string $unit): static
    {
        $this->unit = StringUtils::trim($unit);

        return $this;
    }
}
