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

namespace App\Tests\Model;

use App\Model\HelpDownloadQuery;
use PHPUnit\Framework\TestCase;

final class HelpDownloadQueryTest extends TestCase
{
    public function testProperties(): void
    {
        $actual = new HelpDownloadQuery(1, 'location', 'image');
        self::assertSame(1, $actual->index);
        self::assertSame('location', $actual->location);
        self::assertSame('image', $actual->image);
    }
}
