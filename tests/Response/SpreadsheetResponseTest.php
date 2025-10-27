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

use App\Response\SpreadsheetResponse;
use App\Spreadsheet\SpreadsheetDocument;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\TestCase;

final class SpreadsheetResponseTest extends TestCase
{
    use TranslatorMockTrait;

    public function testGetFileExtension(): void
    {
        $translator = $this->createMockTranslator();
        $doc = new SpreadsheetDocument($translator);
        $response = new SpreadsheetResponse($doc);
        self::assertSame('xlsx', $response->getFileExtension());
    }
}
