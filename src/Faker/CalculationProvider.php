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

use App\Entity\CalculationState;
use App\Entity\Customer;
use App\Entity\Product;
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
     * @var Customer[]
     */
    private $customers;

    /**
     * @var Product[]
     */
    private $products;

    /**
     * @var CalculationState[]
     */
    private $states;

    /**
     * Constructor.
     */
    public function __construct(Generator $generator, EntityManagerInterface $manager)
    {
        parent::__construct($generator);
        $this->products = $manager->getRepository(Product::class)->findAll();
        $this->customers = $manager->getRepository(Customer::class)->findAll();
        $this->states = $manager->getRepository(CalculationState::class)->findBy(['editable' => true]);
    }

    /**
     * Gets a random calculation state.
     */
    public function calculationState(): CalculationState
    {
        return $this->randomElement($this->states);
    }

    /**
     * Gets a random customer.
     */
    public function customer(): Customer
    {
        return $this->randomElement($this->customers);
    }

    /**
     * Gets a random product.
     */
    public function product(): Product
    {
        return $this->randomElement($this->products);
    }
}
