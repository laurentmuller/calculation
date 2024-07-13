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

namespace App\Tests\Spreadsheet;

use App\Spreadsheet\ColumnFormat;
use App\Spreadsheet\SpreadsheetDocument;
use App\Tests\TranslatorMockTrait;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ColumnFormatTest extends TestCase
{
    use TranslatorMockTrait;

    public static function getAlignments(): \Generator
    {
        yield [Alignment::HORIZONTAL_RIGHT, ColumnFormat::AMOUNT];
        yield [Alignment::HORIZONTAL_RIGHT, ColumnFormat::AMOUNT_ZERO];
        yield [Alignment::HORIZONTAL_RIGHT, ColumnFormat::INT];
        yield [Alignment::HORIZONTAL_RIGHT, ColumnFormat::PERCENT];
        yield [Alignment::HORIZONTAL_RIGHT, ColumnFormat::PERCENT_DECIMALS];
        yield [Alignment::HORIZONTAL_CENTER, ColumnFormat::DATE];
        yield [Alignment::HORIZONTAL_CENTER, ColumnFormat::DATE_TIME];
        yield [Alignment::HORIZONTAL_CENTER, ColumnFormat::ID];
        yield [Alignment::HORIZONTAL_CENTER, ColumnFormat::YES_NO];
    }

    #[DataProvider('getAlignments')]
    public function testAlignment(string $expected, ColumnFormat $format): void
    {
        self::assertSame($expected, $format->alignment());
    }

    public function testApply(): void
    {
        $doc = new SpreadsheetDocument($this->createMockTranslator());
        $sheet = $doc->getSheet(0);
        $sheet->setCellValue('A1', 0.25);
        ColumnFormat::AMOUNT->apply($sheet, 1);
        $actual = $sheet->getColumnStyle(1)
            ->getNumberFormat()
            ->getFormatCode();
        self::assertSame(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, $actual);
    }
}
