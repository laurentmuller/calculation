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

use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
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
use App\Utils\FormatUtils;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for the Faker bundle.
 *
 * @see https://fakerphp.github.io/
 */
class FakerService
{
    private ?Generator $generator = null;

    public function __construct(private readonly EntityManagerInterface $manager)
    {
    }

    /**
     * Gets the fake generator.
     */
    public function getGenerator(string $locale = FormatUtils::DEFAULT_LOCALE): Generator
    {
        if (!$this->generator instanceof Generator) {
            $manager = $this->manager;
            $generator = Factory::create($locale);
            // customer providers
            $generator->addProvider(new CustomPerson($generator));
            $generator->addProvider(new CustomCompany($generator));
            $generator->addProvider(new CustomAddress($generator));
            $generator->addProvider(new CustomPhoneNumber($generator));
            // entity providers
            $generator->addProvider(new UserProvider($generator, $manager->getRepository(User::class)));
            $generator->addProvider(new ProductProvider($generator, $manager->getRepository(Product::class)));
            $generator->addProvider(new CategoryProvider($generator, $manager->getRepository(Category::class)));
            $generator->addProvider(new CalculationStateProvider($generator, $manager->getRepository(CalculationState::class)));
            $this->generator = $generator;
        }

        return $this->generator;
    }
}
