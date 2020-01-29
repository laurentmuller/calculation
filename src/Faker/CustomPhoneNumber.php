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

namespace App\Faker;

/**
 * Faker provider to generate custom phone numbers.
 *
 * @author Laurent Muller
 */
class CustomPhoneNumber extends \Faker\Provider\fr_CH\PhoneNumber
{
    /**
     * Swiss phone number formats.
     *
     * @var string[]
     */
    protected static $formats = [
        '0## ### ## ##',
    ];

    /**
     * Swiss mobile (cell) phone number formats.
     *
     * @var string[]
     */
    protected static $mobileFormats = [
        // Local
        '075 ### ## ##',
        '076 ### ## ##',
        '077 ### ## ##',
        '078 ### ## ##',
        '079 ### ## ##',
    ];
}
