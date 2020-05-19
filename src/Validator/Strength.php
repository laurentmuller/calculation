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

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Strength constraint.
 *
 * @author Laurent Muller
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class Strength extends Constraint
{
    /**
     * The disable level.
     */
    public const LEVEL_DISABLE = -1;

    /**
     * The maximum level.
     */
    public const LEVEL_MAX = 4;

    /**
     * The minimum level.
     */
    public const LEVEL_MIN = 0;

    /**
     * The password strength (Value from 0 to 4 or -1 to disable).
     *
     * @var int
     */
    public $minStrength = self::LEVEL_DISABLE;

    /**
     * The password strength message.
     *
     * @var string
     */
    public $minStrengthMessage = 'The password is to weak.';
}
