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

namespace App\Calendar;

/**
 * Trait to check for model class name.
 *
 * @author Laurent Muller
 */
trait ModelTrait
{
    /**
     * Checks if the given class name exist.
     *
     * @param string|null $className    the class name to verify
     * @param string      $defaultClass the default class name to use if the class name si null
     *
     * @return string the class name if no exception
     *
     * @throws CalendarException if the given class name does not exist
     */
    protected function checkClass(?string $className, string $defaultClass): string
    {
        $name = $className ?: $defaultClass;
        if (!\class_exists($name)) {
            throw new CalendarException("Class '{$name}' not found.");
        }

        return $name;
    }
}
