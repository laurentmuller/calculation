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

namespace App\Traits;

use App\Security\EntityVoter;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trait to set or get access rights.
 *
 * @property int[] $EntityCalculation
 * @property int[] $EntityCalculationState
 * @property int[] $EntityCategory
 * @property int[] $EntityCustomer
 * @property int[] $EntityGlobalMargin
 * @property int[] $EntityProduct
 * @property int[] $EntityUser
 *
 * @author Laurent Muller
 */
trait RightsTrait
{
    use MathTrait;

    /**
     * The overwrite rights flag.
     *
     * @ORM\Column(type="boolean", options={"default": 0})
     *
     * @var bool
     */
    protected $overwrite = false;

    /**
     * The rights.
     *
     * @ORM\Column(type="json", nullable=true)
     *
     * @var ?int[]
     */
    protected $rights;

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($this->nameExists($name)) {
            return $this->getEntityRights($name);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function __isset(string $name)
    {
        return $this->nameExists($name);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     */
    public function __set(string $name, $value): void
    {
        if ($this->nameExists($name) && \is_array($value)) {
            $this->setEntityRights($name, $value);
        }
    }

    /**
     * Gets the rights.
     *
     * @return int[]
     */
    public function getRights(): array
    {
        return $this->rights ?? EntityVoter::getEmptyRights();
    }

    /**
     * Gets a value indicating if this righs overwrite the default rights.
     *
     * @return bool true if overwrite, false to use the default rights
     */
    public function isOverwrite(): bool
    {
        return $this->overwrite;
    }

    /**
     * Sets a value indicating if this righs overwrite the default rights.
     */
    public function setOverwrite(bool $overwrite): self
    {
        $this->overwrite = $overwrite;

        return $this;
    }

    /**
     * Sets the rights.
     */
    public function setRights(?array $rights): self
    {
        if (empty($rights) || 0 === \array_sum($rights)) {
            $this->rights = null;
        } else {
            $this->rights = $rights;
        }

        return $this;
    }

    /**
     * Gets the rights for the given entity.
     *
     * @param string $entity the entity name
     *
     * @return int[] the rights
     */
    private function getEntityRights(string $entity): array
    {
        // get offset
        $offset = EntityVoter::getEntityOffset($entity);
        if (EntityVoter::INVALID_VALUE === $offset) {
            return [];
        }

        // get value
        $value = $this->getRights()[$offset];
        if (0 === $value) {
            return [];
        }

        // filter
        return \array_filter(EntityVoter::MASK_ATTRIBUTES, function (int $attribute) use ($value) {
            return $this->isBitSet($value, $attribute);
        });
    }

    /**
     * Returns if the given property name exists.
     *
     * @param string $name the property name to be tested
     *
     * @return bool true if exists; false otherwise
     */
    private function nameExists(string $name): bool
    {
        $entities = \array_keys(EntityVoter::ENTITY_OFFSETS);

        return \in_array($name, $entities, true);
    }

    /**
     * Sets the rights for the given entity.
     *
     * @param string $entity the entity name
     * @param int[]  $rights the rights to set
     */
    private function setEntityRights(string $entity, array $rights): self
    {
        // get offset
        $offset = EntityVoter::getEntityOffset($entity);
        if (EntityVoter::INVALID_VALUE !== $offset) {
            // update
            $value = \array_sum($rights);
            $newRights = $this->getRights();
            $newRights[$offset] = $value;

            return $this->setRights($newRights);
        }

        return $this;
    }
}
