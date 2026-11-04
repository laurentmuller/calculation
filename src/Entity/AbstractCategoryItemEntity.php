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

use App\Interfaces\TimestampableInterface;
use App\Traits\TimestampableTrait;
use App\Utils\StringUtils;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Abstract entity with a category, a unit, and a supplier.
 */
#[ORM\MappedSuperclass]
abstract class AbstractCategoryItemEntity extends AbstractEntity implements TimestampableInterface
{
    use TimestampableTrait;

    /** The parent's category. */
    public ?Category $category = null;

    /** The supplier. */
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    public ?string $supplier = null {
        set => StringUtils::trim($value);
    }

    /** The unit. */
    #[Assert\Length(max: 15)]
    #[ORM\Column(length: 15, nullable: true)]
    public ?string $unit = null {
        set => StringUtils::trim($value);
    }

    /**
     * Gets the category code.
     */
    public function getCategoryCode(): string
    {
        return $this->category->code ?? '';
    }

    /**
     * Gets the category identifier.
     */
    public function getCategoryId(): ?int
    {
        return $this->category?->id;
    }

    /**
     * Gets the group.
     */
    public function getGroup(): ?Group
    {
        return $this->category?->group;
    }

    /**
     * Gets the group code.
     */
    public function getGroupCode(): string
    {
        return $this->getGroup()->code ?? '';
    }

    /**
     * Set category.
     */
    public function withCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Sets the supplier.
     */
    public function withSupplier(?string $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    /**
     * Set unit.
     */
    public function withUnit(?string $unit): static
    {
        $this->unit = $unit;

        return $this;
    }
}
