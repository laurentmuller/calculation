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

    public function getCategory(?Group $group = null, string $code = 'Test Category'): Category
    {
        if ($this->category instanceof Category) {
            return $this->category;
        }

        $this->category = new Category();
        $this->category->setCode($code);
        $group ??= $this->getGroup();
        $group->addCategory($this->category);

        return $this->addEntity($this->category);
    }

    protected function deleteCategory(): void
    {
        if ($this->category instanceof Category) {
            $this->category = $this->deleteEntity($this->category);
        }
        $this->deleteGroup();
    }
}
