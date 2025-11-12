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

namespace App\Tests\Attribute;

use App\Attribute\AddEntityRoute;
use App\Attribute\CloneEntityRoute;
use App\Attribute\DeleteEntityRoute;
use App\Attribute\EditEntityRoute;
use App\Attribute\ExcelRoute;
use App\Attribute\GetDeleteRoute;
use App\Attribute\GetPostRoute;
use App\Attribute\GetRoute;
use App\Attribute\IndexRoute;
use App\Attribute\PdfRoute;
use App\Attribute\PostRoute;
use App\Attribute\ShowEntityRoute;
use App\Attribute\WordRoute;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

/**
 * Unit test for route attributes.
 */
final class RouteTest extends TestCase
{
    private const NAME_VALUE = 'value_edit';
    private const PATH_VALUE = '/edit/{id}';
    private const REQUIREMENTS = ['id' => Requirement::DIGITS];

    public static function getRoutes(): \Generator
    {
        $actual = new GetRoute(self::PATH_VALUE, self::NAME_VALUE);
        yield [
            $actual,
            self::PATH_VALUE,
            self::NAME_VALUE,
            Request::METHOD_GET,
        ];
        $actual = new GetRoute(self::PATH_VALUE, self::NAME_VALUE, self::REQUIREMENTS);
        yield [
            $actual,
            self::PATH_VALUE,
            self::NAME_VALUE,
            Request::METHOD_GET,
            self::REQUIREMENTS,
        ];

        $actual = new GetDeleteRoute(self::PATH_VALUE, self::NAME_VALUE);
        yield [
            $actual,
            self::PATH_VALUE,
            self::NAME_VALUE,
            [Request::METHOD_GET, Request::METHOD_DELETE],
        ];
        $actual = new GetDeleteRoute(self::PATH_VALUE, self::NAME_VALUE, self::REQUIREMENTS);
        yield [
            $actual,
            self::PATH_VALUE,
            self::NAME_VALUE,
            [Request::METHOD_GET, Request::METHOD_DELETE],
            self::REQUIREMENTS,
        ];

        $actual = new PostRoute(self::PATH_VALUE, self::NAME_VALUE);
        yield [
            $actual,
            self::PATH_VALUE,
            self::NAME_VALUE,
            Request::METHOD_POST,
        ];
        $actual = new PostRoute(self::PATH_VALUE, self::NAME_VALUE, self::REQUIREMENTS);
        yield [
            $actual,
            self::PATH_VALUE,
            self::NAME_VALUE,
            Request::METHOD_POST,
            self::REQUIREMENTS,
        ];

        $actual = new GetPostRoute(self::PATH_VALUE, self::NAME_VALUE);
        yield [
            $actual,
            self::PATH_VALUE,
            self::NAME_VALUE,
            [Request::METHOD_GET, Request::METHOD_POST],
        ];
        $actual = new GetPostRoute(self::PATH_VALUE, self::NAME_VALUE, self::REQUIREMENTS);
        yield [
            $actual,
            self::PATH_VALUE,
            self::NAME_VALUE,
            [Request::METHOD_GET, Request::METHOD_POST],
            self::REQUIREMENTS,
        ];

        $actual = new AddEntityRoute();
        yield [
            $actual,
            '/add',
            'add',
            [Request::METHOD_GET, Request::METHOD_POST],
        ];

        $actual = new CloneEntityRoute();
        yield [
            $actual,
            '/clone/{id}',
            'clone',
            [Request::METHOD_GET, Request::METHOD_POST],
            self::REQUIREMENTS,
        ];

        $actual = new DeleteEntityRoute();
        yield [
            $actual,
            '/delete/{id}',
            'delete',
            [Request::METHOD_GET, Request::METHOD_DELETE],
            self::REQUIREMENTS,
        ];

        $actual = new EditEntityRoute();
        yield [
            $actual,
            '/edit/{id}',
            'edit',
            [Request::METHOD_GET, Request::METHOD_POST],
            self::REQUIREMENTS,
        ];

        $actual = new ShowEntityRoute();
        yield [
            $actual,
            '/show/{id}',
            'show',
            Request::METHOD_GET,
            self::REQUIREMENTS,
        ];

        $actual = new IndexRoute();
        yield [
            $actual,
            '',
            'index',
            Request::METHOD_GET,
        ];

        $actual = new ExcelRoute();
        yield [
            $actual,
            '/excel',
            'excel',
            Request::METHOD_GET,
        ];

        $actual = new PdfRoute();
        yield [
            $actual,
            '/pdf',
            'pdf',
            Request::METHOD_GET,
        ];

        $actual = new WordRoute();
        yield [
            $actual,
            '/word',
            'word',
            Request::METHOD_GET,
        ];
    }

    /**
     * @phpstan-param Request::METHOD_*[]|Request::METHOD_* $methods
     */
    #[DataProvider('getRoutes')]
    public function testRoute(
        Route $actual,
        string $path,
        string $name,
        array|string $methods,
        array $requirements = [],
    ): void {
        if (\is_string($methods)) {
            $methods = (array) $methods;
        }
        self::assertSame($path, $actual->getPath());
        self::assertSame($name, $actual->getName());
        self::assertSame($methods, $actual->getMethods());
        self::assertSame($requirements, $actual->getRequirements());
    }
}
