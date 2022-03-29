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

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Abstract entity with a category, a unit and a supplier.
 *
 * A protected $category variable must declared.
 *
 * @author Laurent Muller
 *
 * @ORM\MappedSuperclass
 *
 * @property Category|null $category
 */
abstract class AbstractCategoryItemEntity extends AbstractEntity
{
    /**
     * The supplier.
     *
     * @ORM\Column(length=255, nullable=true)
     * @Assert\Length(max=255)
     */
    protected ?string $supplier = null;
    /**
     * The unit.
     *
     * @ORM\Column(type="string", length=15, nullable=true)
     * @Assert\Length(max=15)
     */
    protected ?string $unit = null;

    /**
     * Get category.
     *
     * @psalm-suppress all
     */
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * Gets the category code.
     */
    public function getCategoryCode(): ?string
    {
        $category = $this->getCategory();

        return $category?->getCode();
    }

    /**
     * Gets the category identifier.
     */
    public function getCategoryId(): ?int
    {
        $category = $this->getCategory();

        return $category?->getId();
    }

    /**
     * Gets the group.
     */
    public function getGroup(): ?Group
    {
        $category = $this->getCategory();

        return $category?->getGroup();
    }

    /**
     * Gets the group code.
     */
    public function getGroupCode(): ?string
    {
        $category = $this->getCategory();

        return $category?->getGroupCode();
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
     *
     * @psalm-suppress UndefinedThisPropertyAssignment
     */
    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Sets the supplier.
     */
    public function setSupplier(?string $supplier): self
    {
        $this->supplier = $this->trim($supplier);

        return $this;
    }

    /**
     * Set unit.
     */
    public function setUnit(?string $unit): self
    {
        $this->unit = $this->trim($unit);

        return $this;
    }
}
