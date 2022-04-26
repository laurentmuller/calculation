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
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CustomCompany extends \Faker\Provider\fr_CH\Company
{
    /** @psalm-suppress MissingPropertyType */
    protected static $formats = [
        '{{lastName}} {{companySuffix}}',
        '{{lastName}} {{firstName}} {{companySuffix}}',
    ];
}
