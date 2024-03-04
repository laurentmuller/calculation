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
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

#[\PHPUnit\Framework\Attributes\CoversClass(ProductUpdateService::class)]
class ProductUpdateServiceTest extends TestCase
{
    private ?Category $category = null;
    private ?Product $product = null;
    private ?User $user = null;

    protected function setUp(): void
    {
        $this->category = new Category();
        $this->category->setCode('category');
        $this->product = new Product();
        $this->product->setDescription('description')
            ->setCategory($this->category)
            ->setPrice(1.0);
        $this->user = new User();
        $this->user->setUsername('system');
    }

    public static function getFixedRounded(): \Iterator
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

    /**
     * @throws Exception
     */
    public function testEmptyProducts(): void
    {
        $service = $this->createService();

        $query = $this->createQuery(ProductUpdateQuery::UPDATE_FIXED);

        $result = $service->update($query);
        self::assertFalse($result->isValid());
        self::assertCount(0, $result->getProducts());
    }

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getFixedRounded')]
    public function testFixedRounded(float $price, float $expected, float $fixed = 1.0): void
    {
        self::assertNotNull($this->product);
        $this->product->setPrice($price);

        $query = $this->createQuery(ProductUpdateQuery::UPDATE_FIXED, $this->product)
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

    /**
     * @throws Exception
     */
    public function testFixedValue(): void
    {
        $price = 2.0;
        $fixed = 1.0;
        $expected = $price + $fixed;

        self::assertNotNull($this->product);
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

    /**
     * @throws Exception
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('getPercentRounded')]
    public function testPercentRounded(float $price, float $percent, float $expected): void
    {
        self::assertNotNull($this->product);
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

    /**
     * @throws Exception
     */
    public function testPercentValue(): void
    {
        $price = 1.0;
        $percent = 0.1;
        $expected = $price * (1.0 + $percent);

        self::assertNotNull($this->product);
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
     * @psalm-param ProductUpdateQuery::UPDATE_* $type
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

    /**
     * @throws Exception
     */
    private function createService(): ProductUpdateService
    {
        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->expects(self::any())
            ->method('findByCategory')
            ->willReturn([$this->product]);

        $categoryRepository = $this->createMock(CategoryRepository::class);
        $categoryRepository->expects(self::any())
            ->method('find')
            ->willReturn($this->category);

        $security = $this->createMock(Security::class);
        $security->expects(self::any())
            ->method('getUser')
            ->willReturn($this->user);

        $service = $this->createMock(SuspendEventListenerService::class);

        return new ProductUpdateService(
            $productRepository,
            $categoryRepository,
            $service,
            $security,
        );
    }

    private static function round05(float $value): float
    {
        return \round($value * 20.0) / 20.0;
    }
}
