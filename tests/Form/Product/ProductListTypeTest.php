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

use App\Entity\Product;
use App\Form\Product\ProductListType;
use App\Repository\ProductRepository;
use App\Tests\Data\DataForm;
use App\Tests\Entity\IdTrait;
use App\Tests\Form\ManagerRegistryTrait;
use App\Tests\Form\PreloadedExtensionsTrait;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;

#[CoversClass(ProductListType::class)]
class ProductListTypeTest extends TypeTestCase
{
    use IdTrait;
    use ManagerRegistryTrait;
    use PreloadedExtensionsTrait;

    private ?Product $product = null;

    /**
     * @throws \ReflectionException
     */
    public function testFormView(): void
    {
        $product = $this->getProduct();
        $formData = DataForm::instance($product);

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
        $model = DataForm::instance($product);
        $form = $this->factory->createBuilder(FormType::class, $model)
            ->add('value', ProductListType::class)
            ->getForm();
        $expected = DataForm::instance($product);
        $form->submit($formData);
        self::assertTrue($form->isSynchronized());
        self::assertEqualsCanonicalizing($expected, $model);
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getPreloadedExtensions(): array
    {
        return [
            new EntityType($this->getProductRegistry()),
        ];
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getProductRegistry(): MockObject&ManagerRegistry
    {
        return $this->createManagerRegistry(
            Product::class,
            ProductRepository::class,
            'getQueryBuilderByCategory',
            [$this->getProduct()]
        );
    }

    /**
     * @throws \ReflectionException
     */
    private function getProduct(): Product
    {
        if (!$this->product instanceof Product) {
            $this->product = new Product();
            $this->product->setDescription('Description');

            return $this->setId($this->product);
        }

        return $this->product;
    }
}
