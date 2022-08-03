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

namespace App\Form\DataTransformer;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Data transformer to convert a category to an identifier.
 *
 * @extends AbstractEntityTransformer<Category>
 */
class CategoryTransformer extends AbstractEntityTransformer
{
    /**
     * Constructor.
     */
    public function __construct(EntityManagerInterface $manager)
    {
        parent::__construct($manager, Category::class);
    }
}
