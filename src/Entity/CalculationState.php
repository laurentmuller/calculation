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

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a calculation state.
 *
 * @ORM\Entity(repositoryClass="App\Repository\CalculationStateRepository")
 * @ORM\Table(name="sy_CalculationState")
 * @UniqueEntity(fields="code", message="state.unique_code")
 */
class CalculationState extends BaseEntity
{
    /**
     * The code (unique).
     *
     * @ORM\Column(name="code", type="string", length=30, unique=true)
     * @Assert\NotBlank
     * @Assert\Length(max=30)
     *
     * @var string
     */
    protected $code;

    /**
     * The color used in the user interface (UI).
     *
     * @ORM\Column(name="color", type="string", length=10, options={"default": "#000000"})
     * @Assert\NotBlank
     * @Assert\Length(max=10)
     *
     * @var string
     */
    protected $color;

    /**
     * The description.
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     *
     * @var string
     */
    protected $description;

    /**
     * The editable state.
     *
     * @ORM\Column(name="editable", type="boolean", options={"default": true})
     *
     * @var bool
     */
    protected $editable;

    /**
     * The list of calculations that fall into this category.
     *
     * @ORM\OneToMany(targetEntity="Calculation", mappedBy="state")
     *
     * @var Collection|Calculation
     */
    private $calculations;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setEditable(true);
    }

    /**
     * Gets the number of calculations.
     */
    public function countCalculations(): int
    {
        return $this->calculations->count();
    }

    /**
     * Gets the calculations.
     *
     * @return Collection|Calculation[]
     */
    public function getCalculations(): Collection
    {
        return $this->calculations;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Get color.
     *
     * @return string
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     *
     * @see \App\Entity\BaseEntity::getDisplay()
     */
    public function getDisplay(): string
    {
        return $this->getCode();
    }

    /**
     * Returns if this state contains one or more calculations.
     *
     * @return bool true if contains calculations
     */
    public function hasCalculations(): bool
    {
        return !$this->calculations->isEmpty();
    }

    /**
     * Creates a calculation state.
     *
     * @param int    $id          the state identifier
     * @param string $code        the state code
     * @param string $description the state description
     *
     * @return \App\Entity\CalculationState
     */
    public static function instance(?int $id, string $code, ?string $description)
    {
        $state = new self();
        $state->id = $id;
        $state->setCode($code)->setDescription($description);

        return $state;
    }

    /**
     * Get editable.
     */
    public function isEditable(): bool
    {
        return $this->editable;
    }

    /**
     * Set code.
     *
     * @param string $code
     */
    public function setCode(?string $code): self
    {
        $this->code = $this->trim($code);

        return $this;
    }

    /**
     * Set color.
     *
     * @param string $color
     */
    public function setColor(?string $color): self
    {
        $this->color = $this->trim($color);

        return $this;
    }

    /**
     * Set description.
     *
     * @param string $description
     */
    public function setDescription(?string $description): self
    {
        $this->description = $this->trim($description);

        return $this;
    }

    /**
     * Set editable.
     */
    public function setEditable(bool $editable): self
    {
        $this->editable = $editable;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchTerms(): array
    {
        return [
            $this->code,
            $this->description,
        ];
    }
}
