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

namespace App\Tests\EntityTrait;

use App\Entity\Category;
use App\Entity\Group;

/**
 * Trait to manage a category.
 */
trait CategoryTrait
{
    use GroupTrait;

    private ?Category $category = null;

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function getCategory(Group $group, string $code = 'Test Category'): Category
    {
        if (!$this->category instanceof Category) {
            $this->category = new Category();
            $this->category->setGroup($group)
                ->setCode($code);
            $this->addEntity($this->category);
        }

        return $this->category; // @phpstan-ignore-line
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteCategory(): void
    {
        if ($this->category instanceof Category) {
            $this->category = $this->deleteEntity($this->category);
        }
        $this->deleteGroup();
    }
}
