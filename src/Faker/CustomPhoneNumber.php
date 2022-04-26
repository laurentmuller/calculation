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

namespace App\Faker;

/**
 * Faker provider to generate custom phone numbers.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CustomPhoneNumber extends \Faker\Provider\fr_CH\PhoneNumber
{
    /**
     * Swiss phone number formats.
     *
     * @psalm-var mixed
     */
    protected static $formats = [
        '0## ### ## ##',
    ];

    /**
     * Swiss mobile (cell) phone number formats.
     *
     * @psalm-var array
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
