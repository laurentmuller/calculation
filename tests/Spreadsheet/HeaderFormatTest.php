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

use App\Spreadsheet\HeaderFormat;
use App\Spreadsheet\SpreadsheetDocument;
use App\Spreadsheet\WorksheetDocument;
use App\Tests\TranslatorMockTrait;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PHPUnit\Framework\TestCase;

final class HeaderFormatTest extends TestCase
{
    use TranslatorMockTrait;

    public function testAmount(): void
    {
        $format = HeaderFormat::amount(Alignment::VERTICAL_CENTER);
        $style = $this->validate($format, Alignment::HORIZONTAL_RIGHT, Alignment::VERTICAL_CENTER);
        $actual = $style->getNumberFormat()->getFormatCode();
        self::assertSame('#,##0.00', $actual);
    }

    public function testAmountZero(): void
    {
        $format = HeaderFormat::amountZero();
        $style = $this->validate($format, Alignment::HORIZONTAL_RIGHT, Alignment::VERTICAL_BOTTOM);
        $actual = $style->getNumberFormat()->getFormatCode();
        self::assertSame('[Red][<=0]#,##0.00;#,##0.00', $actual);
    }

    public function testCenter(): void
    {
        $format = HeaderFormat::center();
        $this->validate($format, Alignment::HORIZONTAL_CENTER, Alignment::VERTICAL_BOTTOM);
    }

    public function testDate(): void
    {
        $format = HeaderFormat::date();
        $style = $this->validate($format, Alignment::HORIZONTAL_CENTER, Alignment::VERTICAL_BOTTOM);
        $actual = $style->getNumberFormat()->getFormatCode();
        self::assertSame('dd/mm/yyyy', $actual);
    }

    public function testDateTime(): void
    {
        $format = HeaderFormat::dateTime();
        $style = $this->validate($format, Alignment::HORIZONTAL_CENTER, Alignment::VERTICAL_BOTTOM);
        $actual = $style->getNumberFormat()->getFormatCode();
        self::assertSame('dd/mm/yyyy hh:mm', $actual);
    }

    public function testDefault(): void
    {
        $format = new HeaderFormat();
        $this->validate($format, Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_BOTTOM);
    }

    public function testId(): void
    {
        $format = HeaderFormat::id();
        $style = $this->validate($format, Alignment::HORIZONTAL_CENTER, Alignment::VERTICAL_BOTTOM);
        $actual = $style->getNumberFormat()->getFormatCode();
        self::assertSame('000000', $actual);
    }

    public function testInstance(): void
    {
        $format = HeaderFormat::instance();
        $this->validate($format, Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_BOTTOM);
    }

    public function testInt(): void
    {
        $format = HeaderFormat::int();
        $style = $this->validate($format, Alignment::HORIZONTAL_RIGHT, Alignment::VERTICAL_BOTTOM);
        $actual = $style->getNumberFormat()->getFormatCode();
        self::assertSame('#,##0', $actual);
    }

    public function testLeft(): void
    {
        $format = HeaderFormat::left();
        $this->validate($format, Alignment::HORIZONTAL_LEFT, Alignment::VERTICAL_BOTTOM);
    }

    public function testPercent(): void
    {
        $format = HeaderFormat::percent();
        $style = $this->validate($format, Alignment::HORIZONTAL_RIGHT, Alignment::VERTICAL_BOTTOM);
        $actual = $style->getNumberFormat()->getFormatCode();
        self::assertSame('0%', $actual);
    }

    public function testPercentCustom(): void
    {
        $format = HeaderFormat::percentCustom('0');
        $style = $this->validate($format, Alignment::HORIZONTAL_RIGHT, Alignment::VERTICAL_BOTTOM);
        $actual = $style->getNumberFormat()->getFormatCode();
        self::assertSame('0', $actual);
    }

    public function testPercentDecimals(): void
    {
        $format = HeaderFormat::percentDecimals();
        $style = $this->validate($format, Alignment::HORIZONTAL_RIGHT, Alignment::VERTICAL_BOTTOM);
        $actual = $style->getNumberFormat()->getFormatCode();
        self::assertSame('0.00%', $actual);
    }

    public function testRight(): void
    {
        $format = HeaderFormat::right();
        $this->validate($format, Alignment::HORIZONTAL_RIGHT, Alignment::VERTICAL_BOTTOM);
    }

    public function testYesNo(): void
    {
        $format = HeaderFormat::yesNo();
        $style = $this->validate($format, Alignment::HORIZONTAL_CENTER, Alignment::VERTICAL_BOTTOM);
        $actual = $style->getNumberFormat()->getFormatCode();
        self::assertSame('"common.value_true";;"common.value_false";@', $actual);
    }

    private function getActiveSheet(): WorksheetDocument
    {
        $doc = new SpreadsheetDocument($this->createMockTranslator());
        $sheet = $doc->getActiveSheet();
        $sheet->setCellValue('A1', 'fake');

        return $sheet;
    }

    private function validate(HeaderFormat $format, ?string $horizontal, ?string $vertical): Style
    {
        $sheet = $this->getActiveSheet();
        $format->apply($sheet, 1);
        $style = $sheet->getStyle('A1');
        $alignment = $style->getAlignment();
        self::assertSame($horizontal, $alignment->getHorizontal());
        self::assertSame($vertical, $alignment->getVertical());

        return $style;
    }
}
