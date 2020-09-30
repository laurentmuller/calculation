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
 * Custom address.
 *
 * @author Laurent Muller
 */
class CustomAddress extends \Faker\Provider\fr_CH\Address
{
    /**
     * @var string[]
     */
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

    /**
     * @var string
     */
    protected static $postcodeCity = '{{postcode}} {{city}}';

    /**
     * Returns the postal code (zip) and the city name.
     */
    public function zipCity(): string
    {
        return $this->generator->parse(static::$postcodeCity);
    }
}
