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
 * @see https://github.com/fzaninotto/Faker
 */
class FakerService
{
    /**
     * The faker generator.
     */
    private ?Generator $faker = null;

    /**
     * Gets the faker generator.
     *
     * @param EntityManagerInterface $manager the manager used for the calculation provider
     */
    public function getFaker(EntityManagerInterface $manager = null): Generator
    {
        if (null === $this->faker) {
            $locale = \Locale::getDefault();
            $faker = Factory::create($locale);
            $faker->addProvider(new CustomPerson($faker));
            $faker->addProvider(new CustomCompany($faker));
            $faker->addProvider(new CustomAddress($faker));
            $faker->addProvider(new CustomPhoneNumber($faker));
            $this->faker = $faker;
        }

        if (null !== $manager && !$this->hasCalculationProvider()) {
            $this->faker->addProvider(new CalculationProvider($this->faker, $manager));
        }

        return $this->faker;
    }

    /**
     * Checks if the calculatio provider is already in this list of providers.
     *
     * @return bool true if present
     */
    private function hasCalculationProvider(): bool
    {
        $providers = $this->faker->getProviders();
        foreach ($providers as $provider) {
            if ($provider instanceof CalculationProvider) {
                return true;
            }
        }

        return false;
    }
}
