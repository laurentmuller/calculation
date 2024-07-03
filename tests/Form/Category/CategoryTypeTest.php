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
use App\Tests\Form\GroupTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\PreloadedExtension;

/**
 * @extends EntityTypeTestCase<Category, CategoryType>
 */
#[CoversClass(CategoryType::class)]
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

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getExtensions(): array
    {
        /** @psalm-var array $extensions */
        $extensions = parent::getExtensions();
        $types = [
            new EntityType($this->getRegistry()),
        ];
        $extensions[] = new PreloadedExtension($types, []);

        return $extensions;
    }

    protected function getFormTypeClass(): string
    {
        return CategoryType::class;
    }
}
