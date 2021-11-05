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

/**
 * Extends Genrator with custom methods.
 *
 * @method string                        catchPhrase()
 * @method ?\App\Entity\Category         category()
 * @method int                           categoriesCount()
 * @method ?\App\Entity\Product          product()
 * @method bool                          productExist(string $description)
 * @method string                        productName()
 * @method ?string                       productSupplier()
 * @method ?string                       productUnit()
 * @method \App\Entity\Product[]         products(int $count = 1, bool $allowDuplicates = false)
 * @method int                           productsCount()
 * @method ?\App\Entity\CalculationState state()
 * @method int                           statesCount()
 * @method ?\App\Entity\User             user()
 * @method int                           usersCount()
 *
 * @author Laurent Muller
 */
class Generator extends \Faker\Generator
{
}
