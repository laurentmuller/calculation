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

use App\Service\FontAwesomeIconService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FontAwesomeIconServiceTest extends TestCase
{
    public static function getIcons(): \Generator
    {
        yield ['', null];
        yield ['fa-fw', null];
        yield ['fa-solid', null];
        yield ['fa-arrow-up', null];
        yield ['fa-fw fa-solid', null];
        yield ['fa-fw fa-arrow-up fa-arrow-right', null];

        yield ['fa-solid fa-arrow-up', 'solid/arrow-up.svg'];
        yield ['fa-arrow-up fa-solid', 'solid/arrow-up.svg'];
        yield ['fa-fw fa-arrow-up fa-solid', 'solid/arrow-up.svg'];
        yield ['fa-arrow-up fa-solid fa-fw', 'solid/arrow-up.svg'];
        yield ['fa-2xs fa-arrow-up fa-solid', 'solid/arrow-up.svg'];
        yield ['  fa-2xs  fa-arrow-up  fa-solid  ', 'solid/arrow-up.svg'];
    }

    #[DataProvider('getIcons')]
    public function testPath(string $icon, ?string $expected): void
    {
        $service = new FontAwesomeIconService();
        $actual = $service->getPath($icon);
        self::assertSame($expected, $actual);
    }
}
