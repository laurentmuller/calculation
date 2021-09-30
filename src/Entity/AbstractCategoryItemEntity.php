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
 * Abstract entity with a category and an unit.
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
     * The unit.
     *
     * @ORM\Column(type="string", length=15, nullable=true)
     * @Assert\Length(max=15)
     */
    protected ?string $unit = null;

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
    public function getCategoryCode(): ?string
    {
        return null !== $this->category ? $this->category->getCode() : null;
    }

    /**
     * Gets the category identifier.
     */
    public function getCategoryId(): ?int
    {
        return null !== $this->category ? $this->category->getId() : null;
    }

    /**
     * Gets the group.
     */
    public function getGroup(): ?Group
    {
        return null !== $this->category ? $this->category->getGroup() : null;
    }

    /**
     * Gets the group code.
     */
    public function getGroupCode(): ?string
    {
        return null !== $this->category ? $this->category->getGroupCode() : null;
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
    public function setCategory(?Category $category): self
    {
        $this->category = $category;

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
