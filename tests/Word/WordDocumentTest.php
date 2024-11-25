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

namespace App\Tests\Word;

use App\Word\WordDocument;
use PHPUnit\Framework\TestCase;

class WordDocumentTest extends TestCase
{
    public function testWordDocument(): void
    {
        $doc = new WordDocument();
        self::assertNull($doc->getTitle());
        $doc->setTitle('title');
        self::assertSame('title', $doc->getTitle());
    }
}
