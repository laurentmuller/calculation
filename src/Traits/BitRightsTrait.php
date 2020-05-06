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

/**
 * Trait to set or get access rights.
 *
 * @author Laurent Muller
 */
trait BitRightsTrait
{
    /**
     * The rights.
     */
    protected int $rights = 0;

    /**
     * Gets the rights for the given entity.
     *
     * @param string $entity the entity class name
     *
     * @return int[] the rights
     */
    public function getEntityRights(string $entity): array
    {
        // get offset
        $offset = EntityVoter::getEntityOffset($entity);
        if (EntityVoter::INVALID_VALUE === $offset) {
            return [];
        }

        // get value
        $value = $this->rights >> ($offset * PHP_INT_SIZE);
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
     * Sets the rights for the given entity. Do nothing if the entity is not found.
     *
     * @param string $entity the entity class name
     * @param int[]  $rights the rights to set
     */
    public function setEntityRights(string $entity, array $rights): self
    {
        // get offset
        $offset = EntityVoter::getEntityOffset($entity);
        if (EntityVoter::INVALID_VALUE !== $offset) {
            // filter
            $rights = \array_filter($rights, function (int $var) {
                return \in_array($var, EntityVoter::MASK_ATTRIBUTES, true);
            });

            // update
            $value = \array_sum($rights);
            $this->resetFlag($offset);
            $this->setFlag($offset, $value, true);
        }

        return $this;
    }

    protected function isFlagSet(int $offset, int $flag): bool
    {
        $value = $this->rights >> ($offset * PHP_INT_SIZE);

        return ($value & $flag) === $flag;
    }

    protected function resetFlag(int $offset): self
    {
        $value = 2 ** PHP_INT_SIZE - 1;

        return $this->setFlag($offset, $value, false);
    }

    protected function setFlag(int $offset, int $flag, bool $value): self
    {
        $flag <<= $offset * PHP_INT_SIZE;
        if ($value) {
            $this->rights |= $flag;
        } else {
            $this->rights &= ~$flag;
        }

        return $this;
    }
}
