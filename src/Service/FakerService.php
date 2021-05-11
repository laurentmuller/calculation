<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Service;

use App\Faker\CalculationProvider;
use App\Faker\CustomAddress;
use App\Faker\CustomCompany;
use App\Faker\CustomPerson;
use App\Faker\CustomPhoneNumber;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Faker\Generator;

/**
 * Service for the Faker bundle.
 *
 * @author Laurent Muller
 *
 * @see https://fakerphp.github.io/
 */
class FakerService
{
    private ?Generator $faker = null;

    private EntityManagerInterface $manager;

    /**
     * Constructor.
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Gets the faker generator.
     */
    public function getFaker(): Generator
    {
        if (null === $this->faker) {
            $locale = \Locale::getDefault();
            $faker = Factory::create($locale);
            $faker->addProvider(new CustomPerson($faker));
            $faker->addProvider(new CustomCompany($faker));
            $faker->addProvider(new CustomAddress($faker));
            $faker->addProvider(new CustomPhoneNumber($faker));
            $faker->addProvider(new CalculationProvider($faker, $this->manager));
            $this->faker = $faker;
        }

        return $this->faker;
    }
}
