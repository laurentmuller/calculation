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

namespace App\Tests\Form\Product;

use App\Form\Product\ProductListType;
use App\Tests\Fixture\FixtureDataForm;
use App\Tests\Form\PreloadedExtensionsTrait;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;

final class ProductListTypeTest extends TypeTestCase
{
    use PreloadedExtensionsTrait;
    use ProductTrait;

    /**
     * @throws \ReflectionException
     */
    public function testFormView(): void
    {
        $product = $this->getProduct();
        $formData = FixtureDataForm::instance($product);

        $view = $this->factory->createBuilder(FormType::class, $formData)
            ->add('value', ProductListType::class)
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
        $product = $this->getProduct();
        $formData = [
            'value' => $product->getId(),
        ];
        $model = FixtureDataForm::instance($product);
        $form = $this->factory->createBuilder(FormType::class, $model)
            ->add('value', ProductListType::class)
            ->getForm();
        $expected = FixtureDataForm::instance($product);
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
            $this->getProductEntityType(),
        ];
    }
}
