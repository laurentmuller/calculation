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

namespace App\Tests\Form\Category;

use App\Entity\Category;
use App\Form\Category\CategoryType;
use App\Tests\Form\EntityTypeTestCase;
use App\Tests\Form\Group\GroupTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * @extends EntityTypeTestCase<Category, CategoryType>
 */
class CategoryTypeTest extends EntityTypeTestCase
{
    use GroupTrait;

    protected function getData(): array
    {
        return [
            'code' => 'code',
            'description' => 'description',
            'group' => null,
        ];
    }

    protected function getEntityClass(): string
    {
        return Category::class;
    }

    protected function getFormTypeClass(): string
    {
        return CategoryType::class;
    }

    /**
     * @throws \ReflectionException
     */
    protected function getPreloadedExtensions(): array
    {
        return [
            new EntityType($this->getGroupRegistry()),
        ];
    }
}
