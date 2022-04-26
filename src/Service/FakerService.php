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

namespace App\Service;

use App\Faker\CalculationStateProvider;
use App\Faker\CategoryProvider;
use App\Faker\CustomAddress;
use App\Faker\CustomCompany;
use App\Faker\CustomPerson;
use App\Faker\CustomPhoneNumber;
use App\Faker\Factory;
use App\Faker\Generator;
use App\Faker\ProductProvider;
use App\Faker\UserProvider;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for the Faker bundle.
 *
 * @see https://fakerphp.github.io/
 */
class FakerService
{
    private ?Generator $generator = null;

    /**
     * Constructor.
     */
    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    /**
     * Gets the faker generator.
     */
    public function getGenerator(): Generator
    {
        if (null === $this->generator) {
            $manager = $this->manager;
            $locale = \Locale::getDefault();
            $generator = Factory::create($locale);

            // custom providers
            $generator->addProvider(new CustomPerson($generator));
            $generator->addProvider(new CustomCompany($generator));
            $generator->addProvider(new CustomAddress($generator));
            $generator->addProvider(new CustomPhoneNumber($generator));

            // entity providers
            $generator->addProvider(new UserProvider($generator, $manager));
            $generator->addProvider(new ProductProvider($generator, $manager));
            $generator->addProvider(new CategoryProvider($generator, $manager));
            $generator->addProvider(new CalculationStateProvider($generator, $manager));

            $this->generator = $generator;
        }

        return $this->generator;
    }
}
