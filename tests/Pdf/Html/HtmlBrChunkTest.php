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

namespace App\Tests\Pdf\Html;

use App\Pdf\Html\HtmlBrChunk;
use PHPUnit\Framework\TestCase;

class HtmlBrChunkTest extends TestCase
{
    public function testIsNewLine(): void
    {
        $chunk = new HtmlBrChunk();
        self::assertTrue($chunk->isNewLine());
    }
}
