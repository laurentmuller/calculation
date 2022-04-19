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
 * Custom address.
 *
 * @author Laurent Muller
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CustomAddress extends \Faker\Provider\fr_CH\Address
{
    /** @psalm-var mixed */
    protected static $postcode = [
        '1###',
        '2###',
        '3###',
        '4###',
        '5###',
        '6###',
        '7###',
        '8###',
        '9###',
    ];

    protected static string $postcodeCity = '{{postcode}} {{city}}';

    /**
     * Returns the postal code (zip) and the city name.
     */
    public function zipCity(): string
    {
        return $this->generator->parse(static::$postcodeCity);
    }
}
