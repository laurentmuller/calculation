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

namespace App\Tests\Response;

use App\Response\WordResponse;
use App\Word\WordDocument;
use PHPUnit\Framework\TestCase;

class WordResponseTest extends TestCase
{
    public function testGetFileExtension(): void
    {
        $doc = new WordDocument();
        $response = new WordResponse($doc);
        self::assertSame('docx', $response->getFileExtension());
    }
}
