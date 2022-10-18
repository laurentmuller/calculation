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
 * Extends Generator with custom methods.
 *
 * @method string                       catchPhrase()                                           Gets a catchphrase.
 * @method \App\Entity\Category         category()                                              Gets a random category.
 * @method int                          categoriesCount()                                       Gets the number of categories.
 * @method \App\Entity\Product          product()                                               Gets a random product.
 * @method int                          productsCount()                                         Gets the number of products.
 * @method bool                         productExist(string $description)                       Returns if the given product's description exist.
 * @method string                       productName()                                           Gets a random product's name.
 * @method \App\Entity\Product[]        products(int $count = 1, bool $allowDuplicates = false) Gets random products.
 * @method string|null                  productSupplier()                                       Gets a random product's supplier.
 * @method string|null                  productUnit()                                           Gets a random product's unit.
 * @method \App\Entity\CalculationState state()                                                 Gets a random calculation state. Gets a random calculation state.
 * @method int                          statesCount()                                           Gets the number of calculation states.
 * @method \App\Entity\User             user()                                                  Gets a random user.
 * @method int                          usersCount()                                            Gets the number of users.
 * @method string                       userName()                                              Gets a random username.
 */
class Generator extends \Faker\Generator
{
}
