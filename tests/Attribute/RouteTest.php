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
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

/**
 * Unit test for route attributes.
 */
class RouteTest extends TestCase
{
    private const NAME_VALUE = 'value_edit';
    private const PATH_VALUE = '/edit/{id}';
    private const REQUIREMENTS = ['id' => Requirement::DIGITS];

    public function testAddEntityRoute(): void
    {
        $actual = new AddEntityRoute();
        self::assertSame('add', $actual->getName());
        self::assertSame('/add', $actual->getPath());
        self::assertSame([Request::METHOD_GET, Request::METHOD_POST], $actual->getMethods());
        self::assertSame([], $actual->getRequirements());
    }

    public function testCloneEntityRoute(): void
    {
        $actual = new CloneEntityRoute();
        self::assertSame('clone', $actual->getName());
        self::assertSame('/clone/{id}', $actual->getPath());
        self::assertSame([Request::METHOD_GET, Request::METHOD_POST], $actual->getMethods());
        self::assertSame(self::REQUIREMENTS, $actual->getRequirements());
    }

    public function testDeleteEntityRoute(): void
    {
        $actual = new DeleteEntityRoute();
        self::assertSame('delete', $actual->getName());
        self::assertSame('/delete/{id}', $actual->getPath());
        self::assertSame([Request::METHOD_GET, Request::METHOD_DELETE], $actual->getMethods());
        self::assertSame(self::REQUIREMENTS, $actual->getRequirements());
    }

    public function testEditEntityRoute(): void
    {
        $actual = new EditEntityRoute();
        self::assertSame('edit', $actual->getName());
        self::assertSame('/edit/{id}', $actual->getPath());
        self::assertSame([Request::METHOD_GET, Request::METHOD_POST], $actual->getMethods());
        self::assertSame(self::REQUIREMENTS, $actual->getRequirements());
    }

    public function testExcelRoute(): void
    {
        $actual = new ExcelRoute();
        self::assertSame('excel', $actual->getName());
        self::assertSame('/excel', $actual->getPath());
        self::assertSame([Request::METHOD_GET], $actual->getMethods());
        self::assertSame([], $actual->getRequirements());
    }

    public function testGetDeleteRoute(): void
    {
        $actual = new GetDeleteRoute(self::PATH_VALUE, self::NAME_VALUE, self::REQUIREMENTS);
        self::assertSameRoute($actual, Request::METHOD_GET, Request::METHOD_DELETE);
    }

    public function testGetPostRoute(): void
    {
        $actual = new GetPostRoute(self::PATH_VALUE, self::NAME_VALUE, self::REQUIREMENTS);
        self::assertSameRoute($actual, Request::METHOD_GET, Request::METHOD_POST);
    }

    public function testGetRoute(): void
    {
        $actual = new GetRoute(self::PATH_VALUE, self::NAME_VALUE, self::REQUIREMENTS);
        self::assertSameRoute($actual, Request::METHOD_GET);
    }

    public function testIndexRoute(): void
    {
        $actual = new IndexRoute();
        self::assertSame('index', $actual->getName());
        self::assertSame('', $actual->getPath());
        self::assertSame([Request::METHOD_GET], $actual->getMethods());
        self::assertSame([], $actual->getRequirements());
    }

    public function testPdfRoute(): void
    {
        $actual = new PdfRoute();
        self::assertSame('pdf', $actual->getName());
        self::assertSame('/pdf', $actual->getPath());
        self::assertSame([Request::METHOD_GET], $actual->getMethods());
        self::assertSame([], $actual->getRequirements());
    }

    public function testPostRoute(): void
    {
        $actual = new PostRoute(self::PATH_VALUE, self::NAME_VALUE, self::REQUIREMENTS);
        self::assertSameRoute($actual, Request::METHOD_POST);
    }

    public function testShowEntityRoute(): void
    {
        $actual = new ShowEntityRoute();
        self::assertSame('show', $actual->getName());
        self::assertSame('/show/{id}', $actual->getPath());
        self::assertSame([Request::METHOD_GET], $actual->getMethods());
        self::assertSame(self::REQUIREMENTS, $actual->getRequirements());
    }

    public function testWordRoute(): void
    {
        $actual = new WordRoute();
        self::assertSame('word', $actual->getName());
        self::assertSame('/word', $actual->getPath());
        self::assertSame([Request::METHOD_GET], $actual->getMethods());
        self::assertSame([], $actual->getRequirements());
    }

    protected static function assertSameRoute(Route $actual, string ...$methods): void
    {
        self::assertSame(self::PATH_VALUE, $actual->getPath());
        self::assertSame(self::NAME_VALUE, $actual->getName());
        self::assertSame(self::REQUIREMENTS, $actual->getRequirements());
        self::assertSame($methods, $actual->getMethods());

        // check override methods
        $actual->setMethods('fake');
        self::assertSame($methods, $actual->getMethods());
    }
}
