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
use App\Tests\Fixture\DataForm;
use App\Tests\Form\PreloadedExtensionsTrait;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;

class CategoryListTypeTest extends TypeTestCase
{
    use CategoryTrait;
    use PreloadedExtensionsTrait;

    /**
     * @throws \ReflectionException
     */
    public function testFormView(): void
    {
        $category = $this->getCategory();
        $formData = DataForm::instance($category);

        $view = $this->factory->createBuilder(FormType::class, $formData)
            ->add('value', CategoryListType::class)
            ->getForm()
            ->createView();

        self::assertArrayHasKey('value', $view->vars);
        self::assertEqualsCanonicalizing($formData, $view->vars['value']);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSubmitValidData(): void
    {
        $category = $this->getCategory();
        $formData = [
            'value' => $category->getId(),
        ];
        $model = DataForm::instance($category);
        $form = $this->factory->createBuilder(FormType::class, $model)
            ->add('value', CategoryListType::class)
            ->getForm();
        $expected = DataForm::instance($category);
        $form->submit($formData);
        self::assertTrue($form->isSynchronized());
        self::assertEqualsCanonicalizing($expected, $model);
    }

    /**
     * @throws \ReflectionException
     */
    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        return [
            $this->getCategoryEntityType(),
            new CategoryListType(),
        ];
    }
}
