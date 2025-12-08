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

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use App\Model\ProductUpdateQuery;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\ProductUpdateService;
use App\Service\SuspendEventListenerService;
use App\Tests\Entity\IdTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class ProductUpdateServiceTest extends TestCase
{
    use IdTrait;
    use TranslatorMockTrait;

    private Category $category;
    private Product $product;
    private Session $session;
    private User $user;

    #[\Override]
    protected function setUp(): void
    {
        $this->category = new Category();
        $this->category->setCode('category');
        self::setId($this->category);

        $this->product = new Product();
        $this->product->setDescription('description')
            ->setCategory($this->category)
            ->setPrice(1.0);
        self::setId($this->product);

        $this->user = new User();
        $this->user->setUsername('system');

        $this->session = new Session(new MockArraySessionStorage());
        $this->session->set('product.update.type', ProductUpdateQuery::UPDATE_PERCENT);
        $this->session->set('product.update.percent', 1.0);
        $this->session->set('product.update.fixed', 1.0);
        $this->session->set('product.update.round', false);
        $this->session->set('product.update.category', 1);
    }

    public static function getFixedRounded(): \Generator
    {
        yield [1.00, 2.00];
        yield [1.01, 2.00];
        yield [1.02, 2.00];
        yield [1.025, 2.00];
        yield [1.0251, 2.05];
        yield [1.03, 2.05];
        yield [1.04, 2.05];
        yield [1.05, 2.05];
    }

    public static function getPercentRounded(): \Generator
    {
        $price = 18.8;
        foreach (\range(0.01, 0.09, 0.01) as $percent) {
            $expected = self::round05($price * (1.0 + $percent));
            yield [$price, $percent, $expected];
        }
        yield [44.77, 0.06, 47.45];
        yield [25.41, 0.06, 26.95];
    }

    public function testCreateQuery(): void
    {
        $service = $this->createService();
        $query = $service->createQuery();
        self::assertSame($this->category, $query->getCategory());
    }

    public function testCreateQueryEmpty(): void
    {
        $this->session->set('product.update.category', 0);
        $service = $this->createService();
        $query = $service->createQuery();
        $this->session->set('product.update.category', 1);
        self::assertNull($query->getCategory());
    }

    public function testEmptyProducts(): void
    {
        $service = $this->createService();
        $query = $this->createQuery(ProductUpdateQuery::UPDATE_FIXED);

        $result = $service->update($query);
        self::assertFalse($result->isValid());
        self::assertEmpty($result->getProducts());
    }

    #[DataProvider('getFixedRounded')]
    public function testFixedRounded(float $price, float $expected, float $fixed = 1.0): void
    {
        $this->product->setPrice($price);

        $query = $this->createQuery(ProductUpdateQuery::UPDATE_FIXED, $this->product)
            ->setSimulate(false)
            ->setFixed($fixed)
            ->setRound(true);

        $service = $this->createService();
        $result = $service->update($query);

        self::assertTrue($result->isValid());
        $products = $result->getProducts();
        self::assertCount(1, $products);
        $product = $products[0];
        self::assertProduct($product, $expected);
    }

    public function testFixedValue(): void
    {
        $price = 2.0;
        $fixed = 1.0;
        $expected = $price + $fixed;

        $this->product->setPrice($price);

        $query = $this->createQuery(ProductUpdateQuery::UPDATE_FIXED, $this->product)
            ->setFixed($fixed);

        $service = $this->createService();
        $result = $service->update($query);

        self::assertTrue($result->isValid());
        $products = $result->getProducts();
        self::assertCount(1, $products);
        $product = $products[0];
        self::assertProduct($product, $expected);
    }

    public function testGetAllProducts(): void
    {
        $service = $this->createService();
        $products = $service->getAllProducts();
        self::assertSame([$this->product], $products);
    }

    #[DataProvider('getPercentRounded')]
    public function testPercentRounded(float $price, float $percent, float $expected): void
    {
        $this->product->setPrice($price);

        $query = $this->createQuery(ProductUpdateQuery::UPDATE_PERCENT, $this->product)
            ->setRound(true)
            ->setPercent($percent);

        $service = $this->createService();
        $result = $service->update($query);

        self::assertTrue($result->isValid());
        $products = $result->getProducts();
        self::assertCount(1, $products);
        $product = $products[0];
        self::assertProduct($product, $expected);
    }

    public function testPercentValue(): void
    {
        $price = 1.0;
        $percent = 0.1;
        $expected = $price * (1.0 + $percent);

        $this->product->setPrice($price);

        $query = $this->createQuery(ProductUpdateQuery::UPDATE_PERCENT, $this->product)
            ->setPercent($percent);

        $service = $this->createService();
        $result = $service->update($query);

        self::assertTrue($result->isValid());
        $products = $result->getProducts();
        self::assertCount(1, $products);
        $product = $products[0];
        self::assertProduct($product, $expected);
    }

    public function testSaveQuery(): void
    {
        $service = $this->createService();
        $query = $service->createQuery();
        $service->saveQuery($query);
        self::assertSame(1, $this->session->get('product.update.category'));
    }

    private function assertProduct(mixed $product, ?float $newPrice = null): void
    {
        self::assertIsArray($product);
        self::assertArrayHasKey('description', $product);
        self::assertArrayHasKey('oldPrice', $product);
        self::assertArrayHasKey('newPrice', $product);
        self::assertArrayHasKey('delta', $product);

        if (null !== $newPrice) {
            self::assertSame($newPrice, $product['newPrice']);
        }
    }

    /**
     * @phpstan-param ProductUpdateQuery::UPDATE_* $type
     */
    private function createQuery(string $type, ?Product $product = null): ProductUpdateQuery
    {
        $query = new ProductUpdateQuery();
        $query->setType($type)
            ->setCategory($this->category)
            ->setAllProducts(false);
        if ($product instanceof Product) {
            $query->setProducts([$product]);
        }

        return $query;
    }

    private function createRequestStack(): MockObject&RequestStack
    {
        $request = new Request();
        $request->setSession($this->session);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')
            ->willReturn($request);
        $requestStack->method('getSession')
            ->willReturn($this->session);

        return $requestStack;
    }

    private function createService(): ProductUpdateService
    {
        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->method('findByCategory')
            ->willReturn([$this->product]);

        $productRepository->method('findByDescription')
            ->willReturn([$this->product]);

        $categoryRepository = $this->createMock(CategoryRepository::class);
        $categoryRepository->method('find')
            ->willReturn($this->category);

        $security = $this->createMock(Security::class);
        $security->method('getUser')
            ->willReturn($this->user);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')
            ->willReturn(false);

        $logger = $this->createMock(LoggerInterface::class);
        $suspendEventListenerService = $this->createMock(SuspendEventListenerService::class);
        $productService = new ProductUpdateService(
            $productRepository,
            $categoryRepository,
            $suspendEventListenerService,
            $security,
        );
        $productService->setRequestStack($this->createRequestStack());
        $productService->setTranslator($this->createMockTranslator());
        $productService->setLogger($logger);

        $class = new \ReflectionClass(ProductUpdateService::class);
        $property = $class->getProperty('container');
        $property->setValue($productService, $container);

        return $productService;
    }

    private static function round05(float $value): float
    {
        return \round($value * 20.0) / 20.0;
    }
}
