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

namespace App\Tests\Parameter;

use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\GlobalProperty;
use App\Entity\Product;
use App\Enums\EntityAction;
use App\Enums\StrengthLevel;
use App\Parameter\ApplicationParameters;
use App\Repository\AbstractRepository;
use App\Repository\CalculationStateRepository;
use App\Repository\CategoryRepository;
use App\Repository\GlobalPropertyRepository;
use App\Repository\ProductRepository;
use App\Tests\Entity\IdTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class ApplicationParametersTest extends TestCase
{
    use IdTrait;

    /**
     * @throws Exception
     */
    public function testEnumInt(): void
    {
        $property = GlobalProperty::instance('security_level')
            ->setValue(StrengthLevel::NONE);
        $parameters = $this->createApplication([$property]);
        $parameters->getSecurity()
            ->setLevel(StrengthLevel::MEDIUM);
        $actual = $parameters->save();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testEnumString(): void
    {
        $property = GlobalProperty::instance('edit_action')
            ->setValue(EntityAction::EDIT);
        $parameters = $this->createApplication([$property]);
        $parameters->getDisplay()
            ->setEditAction(EntityAction::NONE);
        $actual = $parameters->save();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception|\ReflectionException
     */
    public function testGetDefaultCategoryNotNull(): void
    {
        $id = 10;
        $category = new Category();
        $category->setCode('fake');
        self::setId($category, $id);

        $property = GlobalProperty::instance('default_category')
            ->setValue($id);

        $parameters = $this->createApplication([$property], $category);
        $actual = $parameters->getDefaultCategory();
        self::assertSame($category, $actual);
    }

    /**
     * @throws Exception
     */
    public function testGetDefaultCategoryNull(): void
    {
        $parameters = $this->createApplication();
        $actual = $parameters->getDefaultCategory();
        self::assertNull($actual);
    }

    /**
     * @throws Exception|\ReflectionException
     */
    public function testGetDefaultProductNotNull(): void
    {
        $id = 10;
        $product = new Product();
        $product->setDescription('fake');
        self::setId($product, $id);

        $property = GlobalProperty::instance('default_product')
            ->setValue($id);

        $parameters = $this->createApplication([$property], product: $product);
        $actual = $parameters->getDefaultProduct();
        self::assertSame($product, $actual);
    }

    /**
     * @throws Exception
     */
    public function testGetDefaultProductNull(): void
    {
        $parameters = $this->createApplication();
        $actual = $parameters->getDefaultProduct();
        self::assertNull($actual);
    }

    /**
     * @throws Exception|\ReflectionException
     */
    public function testGetDefaultStateNotNull(): void
    {
        $id = 10;
        $state = new CalculationState();
        $state->setCode('fake');
        self::setId($state, $id);

        $property = GlobalProperty::instance('default_state')
            ->setValue($id);

        $parameters = $this->createApplication([$property], state: $state);
        $actual = $parameters->getDefaultState();
        self::assertSame($state, $actual);
    }

    /**
     * @throws Exception
     */
    public function testGetDefaultStateNull(): void
    {
        $parameters = $this->createApplication();
        $actual = $parameters->getDefaultState();
        self::assertNull($actual);
    }

    /**
     * @throws Exception
     */
    public function testGetDefaultValues(): void
    {
        $parameters = $this->createApplication();
        $actual = $parameters->getDefaultValues();
        self::assertNotEmpty($actual);
    }

    /**
     * @throws Exception
     */
    public function testIsDebug(): void
    {
        $parameters = $this->createApplication();
        $actual = $parameters->isDebug();
        self::assertFalse($actual);
    }

    /**
     * @throws Exception
     */
    public function testRights(): void
    {
        $parameters = $this->createApplication();
        $actual = $parameters->getRights();
        self::assertNull($actual->getAdminRights());
        self::assertNull($actual->getUserRights());
    }

    /**
     * @throws Exception
     */
    public function testSaveSuccess(): void
    {
        $parameters = $this->createApplication();
        $parameters->getCustomer()
            ->setAddress('fake');
        $parameters->getDate()
            ->setArchive();
        $parameters->getDefault()
            ->setMinMargin(1.0);
        $parameters->getDisplay()
            ->setEditAction(EntityAction::NONE);
        $parameters->getHomePage()
            ->setDarkNavigation(true);
        $parameters->getMessage()
            ->setIcon(false);
        $parameters->getOptions()
            ->setPrintAddress(true);
        $parameters->getProduct()
            ->setQuantity(1.25);
        $parameters->getSecurity()
            ->setLevel(StrengthLevel::MEDIUM)
            ->setCompromised(true);

        $actual = $parameters->save();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testSecurity(): void
    {
        $parameters = $this->createApplication();
        $actual = $parameters->getSecurity();
        self::assertFalse($actual->isCaptcha());
    }

    /**
     * @throws Exception
     */
    public function testWithDefaultValue(): void
    {
        $property = GlobalProperty::instance('security_captcha')
            ->setValue(false);
        $parameters = $this->createApplication([$property]);
        $parameters->getSecurity()
            ->setCaptcha(true);
        $actual = $parameters->save();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception|\ReflectionException
     */
    public function testWithEntity(): void
    {
        $category = new Category();
        $category->setCode('fake');
        self::setId($category);
        $property = GlobalProperty::instance('default_category')
            ->setValue(10);
        $parameters = $this->createApplication([$property]);
        $parameters->getDefault()
            ->setCategoryId(1);
        $actual = $parameters->save();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testWithExistingProperty(): void
    {
        $property = GlobalProperty::instance('security_captcha');
        $property->setValue(true);
        $parameters = $this->createApplication([$property]);
        $parameters->getSecurity()
            ->setCaptcha(false);
        $actual = $parameters->save();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    private function createApplication(
        array $properties = [],
        ?Category $category = null,
        ?CalculationState $state = null,
        ?Product $product = null,
    ): ApplicationParameters {
        $cache = new ArrayAdapter();
        $manager = $this->createMockManager($properties, $category, $state, $product);

        return new ApplicationParameters($cache, $manager, false);
    }

    /**
     * @throws Exception
     */
    private function createMockManager(
        array $properties = [],
        ?Category $category = null,
        ?CalculationState $state = null,
        ?Product $product = null
    ): MockObject&EntityManagerInterface {
        $propertyRepository = $this->createMock(GlobalPropertyRepository::class);
        $propertyRepository->method('findAll')
            ->willReturn($properties);

        $categoryRepository = $this->createMock(CategoryRepository::class);
        $categoryRepository->method('find')
            ->willReturn($category);

        $stateRepository = $this->createMock(CalculationStateRepository::class);
        $stateRepository->method('find')
            ->willReturn($state);

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->method('find')
            ->willReturn($product);

        $callback = fn (string $class): AbstractRepository => match ($class) {
            Category::class => $categoryRepository,
            CalculationState::class => $stateRepository,
            Product::class => $productRepository,
            default => $propertyRepository,
        };

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')
            ->willReturnCallback($callback);

        return $manager;
    }
}
