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
 * Faker provider to generate custom person.
 *
 * @property \Faker\UniqueGenerator $unique
 */
class CustomPerson extends \Faker\Provider\fr_CH\Person
{
    /** @phpstan-var mixed */
    protected static $titleFemale = [
        'Madame',
        'Mademoiselle',
    ];

    /** @phpstan-var mixed */
    protected static $titleMale = ['Monsieur'];
}
