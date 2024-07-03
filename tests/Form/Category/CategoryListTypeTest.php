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

use App\Form\Category\CategoryListType;
use App\Tests\Form\CategoryTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(CategoryListType::class)]
class CategoryListTypeTest extends TypeTestCase
{
    use CategoryTrait;

    /**
     * @throws \ReflectionException
     */
    public function testSubmitValidData(): void
    {
        $category = $this->getCategory();
        $data = [
            'category' => $category,
        ];
        $form = $this->factory->createBuilder(FormType::class, $data)
            ->add('category', CategoryListType::class, ['required' => false])
            ->getForm();
        $form->submit([]);
        self::assertTrue($form->isValid());
        self::assertSame($data['category'], $category);
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getExtensions(): array
    {
        /** @psalm-var array $extensions */
        $extensions = parent::getExtensions();

        $types = [
            $this->getEntityType(),
            new CategoryListType(),
        ];
        $extensions[] = new PreloadedExtension($types, []);

        return $extensions;
    }
}
