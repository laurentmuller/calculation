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

use App\Entity\Category;
use App\Repository\CategoryRepository;

/**
 * Category provider.
 *
 * @template-extends EntityProvider<Category>
 */
class CategoryProvider extends EntityProvider
{
    public function __construct(Generator $generator, CategoryRepository $repository)
    {
        parent::__construct($generator, $repository);
    }

    /**
     * Gets a random category.
     */
    public function category(): ?Category
    {
        return $this->entity();
    }
}
