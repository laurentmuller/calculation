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
 * Faker provider to generate company names.
 *
 * @property \Faker\UniqueGenerator $unique
 */
class CustomCompany extends \Faker\Provider\fr_CH\Company
{
    /**
     * @phpstan-var array
     *
     * @psalm-var mixed
     */
    protected static $formats = [
        '{{lastName}} {{companySuffix}}',
        '{{lastName}} {{firstName}} {{companySuffix}}',
    ];
}
