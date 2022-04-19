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
use Doctrine\ORM\EntityManagerInterface;

/**
 * Category provider.
 *
 * @author Laurent Muller
 *
 * @template-extends EntityProvider<Category>
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CategoryProvider extends EntityProvider
{
    /**
     * Constructor.
     */
    public function __construct(Generator $generator, EntityManagerInterface $manager)
    {
        parent::__construct($generator, $manager, Category::class);
    }

    /**
     * Gets the number of users.
     */
    public function categoriesCount(): int
    {
        return $this->count();
    }

    /**
     * Gets a random category.
     */
    public function category(): ?Category
    {
        return $this->entity();
    }
}
