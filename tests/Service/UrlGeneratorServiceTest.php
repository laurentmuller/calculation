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

use App\Entity\Group;
use App\Interfaces\EntityInterface;
use App\Service\UrlGeneratorService;
use App\Tests\Entity\IdTrait;
use App\Tests\KernelServiceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

class UrlGeneratorServiceTest extends KernelServiceTestCase
{
    use IdTrait;

    private UrlGeneratorService $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(UrlGeneratorService::class);
    }

    public static function getGenerates(): \Generator
    {
        yield ['homepage', '/'];
        yield ['product_edit', '/product/edit/10', ['id' => 10]];
    }

    public static function getRequests(): \Generator
    {
        yield [new Request(), '/'];
        yield [new Request(['caller' => 'products']), 'products'];
        yield [new Request(['id' => 10]), 'id=10'];
        yield [new Request([]), 'id=10', 10];
        yield [new Request(['caller' => 'products', 'sort' => 'asc']), 'products?sort=asc'];
    }

    /**
     * @throws \ReflectionException
     */
    public static function getRouteParams(): \Generator
    {
        yield [new Request(), []];
        yield [new Request(['caller' => 'products']), ['caller' => 'products']];
        yield [new Request(['id' => 10]), ['id' => 10]];
        yield [new Request([]), ['id' => 10], 10];

        $entity = new Group();
        self::setId($entity);
        yield [new Request([]), ['id' => 1], $entity];
    }

    #[DataProvider('getRequests')]
    public function testCancelUrl(Request $request, string $expected, EntityInterface|int|null $id = 0): void
    {
        $actual = $this->service->cancelUrl($request, $id);
        self::assertStringContainsString($expected, $actual);
    }

    #[DataProvider('getGenerates')]
    public function testGenerate(string $name, string $expected, array $parameters = []): void
    {
        $actual = $this->service->generate($name, $parameters);
        self::assertStringContainsString($expected, $actual);
    }

    #[DataProvider('getRequests')]
    public function testRedirect(Request $request, string $expected, EntityInterface|int|null $id = 0): void
    {
        $actual = $this->service->redirect($request, $id);
        self::assertSame(302, $actual->getStatusCode());
        self::assertStringContainsString($expected, $actual->getTargetUrl());
    }

    #[DataProvider('getRouteParams')]
    public function testRouteParams(Request $request, array $expected, EntityInterface|int|null $id = 0): void
    {
        $actual = $this->service->routeParams($request, $id);
        self::assertSame($expected, $actual);
    }
}
