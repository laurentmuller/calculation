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

namespace App\Faker;

use App\Entity\CalculationState;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Generator;
use Faker\Provider\Base;

/**
 * Faker provider to generate calculations.
 *
 * @author Laurent Muller
 */
class CalculationProvider extends Base
{
    /**
     * @var Product[]
     */
    private $products;

    /**
     * @var CalculationState[]
     */
    private $states;

    /**
     * @var User[]
     */
    private $users;

    /**
     * Constructor.
     */
    public function __construct(Generator $generator, EntityManagerInterface $manager)
    {
        parent::__construct($generator);
        $this->products = $manager->getRepository(Product::class)->findAll();
        $this->states = $manager->getRepository(CalculationState::class)->findBy(['editable' => true]);
        $this->users = $manager->getRepository(User::class)->findBy(['enabled' => true]);
    }

    /**
     * Gets the number of products.
     */
    public function countProducts(): int
    {
        return \count($this->products);
    }

    /**
     * Gets a random product.
     */
    public function product(): Product
    {
        return $this->randomElement($this->products);
    }

    /**
     * Gets random products. The products are sorted by category code and description.
     *
     * @return Product[]
     */
    public function products(int $count = 1, bool $allowDuplicates = false): array
    {
        $products = $this->randomElements($this->products, $count, $allowDuplicates);

        \usort($products, static function (Product $a, Product $b) {
            $result = \strcasecmp($a->getCategoryCode(), $b->getCategoryCode());
            if (0 === $result) {
                return \strcasecmp($a->getDescription(), $b->getDescription());
            }

            return $result;
        });

        return $products;
    }

    /**
     * Gets a random calculation state.
     */
    public function state(): CalculationState
    {
        return $this->randomElement($this->states);
    }

    /**
     * Gets a random user.
     */
    public function user(): User
    {
        return $this->randomElement($this->users);
    }

    /**
     * Gets a random user name.
     */
    public function userName(): string
    {
        return $this->user()->getUsername();
    }
}
