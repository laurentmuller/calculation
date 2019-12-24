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
 * @author Laurent Muller
 */
trait RightsTrait
{
    /**
     * The rights.
     *
     * @ORM\Column(type="string", length=20, nullable=true)
     *
     * @var string
     */
    protected $rights;

    /**
     * {@inheritdoc}
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
     */
    public function __set(string $name, $value): void
    {
        if ($this->nameExists($name) && \is_array($value)) {
            $this->setEntityRights($name, $value);
        }
    }

    /**
     * Gets the rights mask.
     *
     * @return int
     */
    public function getRights(): ?string
    {
        return $this->rights;
    }

    /**
     * Sets the rights.
     *
     * @param int $rights
     */
    public function setRights(?string $rights): self
    {
        // check if empty
        $empty = true;
        $len = \strlen((string) $rights);
        for ($i = 0; $i < $len; ++$i) {
            if (isset($rights[$i]) && 0 !== \ord($rights[$i])) {
                $empty = false;
                break;
            }
        }
        $this->rights = $empty ? null : $rights;

        return $this;
    }

    /**
     * Gets the rights for the given entity.
     *
     * @param string $entity the entity class name
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
        $value = EntityVoter::getOffsetValue($this->rights, $offset);
        if (0 === $value) {
            return [];
        }

        // filter
        $attributes = EntityVoter::MASK_ATTRIBUTES;
        $callback = function ($attribute) use ($value) {
            return ($value & $attribute) === $attribute;
        };

        return \array_filter($attributes, $callback);
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
        return \in_array($name, EntityVoter::ENTITIES, true);
    }

    /**
     * Sets the rights for the given entity.
     *
     * @param string $entity the entity class name
     * @param int[]  $rights the rights to set
     */
    private function setEntityRights(string $entity, array $rights): self
    {
        // get offset
        $offset = EntityVoter::getEntityOffset($entity);
        if (EntityVoter::INVALID_VALUE !== $offset) {
            // update
            $value = \array_sum($rights);
            $oldRights = $this->rights;
            $newRights = EntityVoter::setOffsetValue($oldRights, $offset, $value);

            return $this->setRights($newRights);
        }

        return $this;
    }
}
