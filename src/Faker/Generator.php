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

use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use App\Traits\ArrayTrait;
use Faker\Provider\Base;

/**
 * Extends Generator with additional methods.
 *
 * @method string                catchPhrase()                                           Gets a catchphrase.
 * @method Category|null         category()                                              Gets a random category.
 * @method Product|null          product()                                               Gets a random product.
 * @method string                productName()                                           Gets a random product's name.
 * @method string|null           productUnit()                                           Gets a random product's unit.
 * @method string|null           productSupplier()                                       Gets a random product's supplier.
 * @method bool                  productExist(string $description)                       Returns if the given product's description exists.
 * @method Product[]             products(int $count = 1, bool $allowDuplicates = false) Gets random products.
 * @method CalculationState|null state()                                                 Gets a random calculation state.
 * @method User|null             user()                                                  Gets a random user.
 * @method string|null           userName()                                              Gets a random username.
 * @method Base[]                getProviders()                                          Gets the providers.
 */
class Generator extends \Faker\Generator
{
    use ArrayTrait;

    /**
     * Find a provider for the given class name.
     *
     * @template TProvider of Base
     *
     * @param class-string<TProvider> $class the provider class name to find
     *
     * @psalm-return TProvider|null the provider, if found; null otherwise
     */
    public function getProvider(string $class): ?Base
    {
        /** @psalm-var TProvider|null */
        return $this->findFirst($this->getProviders(), static fn (Base $item): bool => $item instanceof $class);
    }
}
