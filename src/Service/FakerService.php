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

namespace App\Service;

use App\Faker\CustomAddress;
use App\Faker\CustomCompany;
use App\Faker\CustomPerson;
use App\Faker\CustomPhoneNumber;
use Faker\Generator;

/**
 * Service for the Faker bundle.
 *
 * @author Laurent Muller
 *
 * @see https://github.com/fzaninotto/Faker
 */
class FakerService
{
    /**
     * The faker generator.
     *
     * @var Generator
     */
    protected $faker;

    /**
     * Gets the faker generator.
     */
    public function getFaker(): Generator
    {
        if (null === $this->faker) {
            $locale = \Locale::getDefault();
            $faker = \Faker\Factory::create($locale);
            $faker->addProvider(new CustomPerson($faker));
            $faker->addProvider(new CustomCompany($faker));
            $faker->addProvider(new CustomAddress($faker));
            $faker->addProvider(new CustomPhoneNumber($faker));
            $this->faker = $faker;
        }

        return $this->faker;
    }
}
