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

namespace App\Tests\Traits;

use App\Tests\KernelServiceTestCase;
use App\Traits\TableCellTrait;
use Twig\Environment;
use Twig\Error\Error;

class TableCellTraitTest extends KernelServiceTestCase
{
    use TableCellTrait;

    protected Environment $twig;

    protected function setUp(): void
    {
        parent::setUp();
        $this->twig = $this->getService(Environment::class);
    }

    /**
     * @throws Error
     */
    public function testWithoutRoute(): void
    {
        $expected = '10';
        $actual = $this->renderCell(
            10,
            ['id' => 1],
            'title',
            false,
            'parameter'
        );
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Error
     */
    public function testWithRoute(): void
    {
        $expected = '<a class="rowlink-skip" href="/?id=1">10</a>';
        $actual = $this->renderCell(
            10,
            ['id' => 1],
            '',
            'homepage',
            'id'
        );
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Error
     */
    public function testWithRouteAndTitle(): void
    {
        $expected = '<a class="rowlink-skip" href="/?id=1" title="My Title">10</a>';
        $actual = $this->renderCell(
            10,
            ['id' => 1],
            'My Title',
            'homepage',
            'id'
        );
        self::assertSame($expected, $actual);
    }
}
