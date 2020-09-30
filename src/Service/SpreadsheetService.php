<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Service;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Document\Properties;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to generate Spreadsheet.
 *
 * @author Laurent Muller
 */
class SpreadsheetService
{
    /**
     * The default margins (0.4" = 10 millimeters).
     */
    public const DEFAULT_MARGIN = 0.4;

    /**
     * The top and bottom margins when header and/or footer is present (0.51" = 13 millimeters).
     */
    public const HEADER_FOOTER_MARGIN = 0.51;

    /**
     * The active worksheet title property (type string).
     */
    public const P_ACTIVE_TITLE = 'activeTitle';

    /**
     * The appication name property (type string).
     */
    public const P_APPLICATION = 'application';

    /**
     * The company property (type string).
     */
    public const P_COMPANY = 'company';

    /**
     * The date property (type string).
     */
    public const P_DATE = 'date';

    /**
     * The print gridlines property (type boolean).
     */
    public const P_GRIDLINE = 'gridline';

    /**
     * The landscape property (type boolean).
     */
    public const P_LANDSCAPE = 'landscape';

    /**
     * The margins property (type float).
     */
    public const P_MARGINS = 'margins';

    /**
     * The paper size property, one of PageSetup::PAPERSIZE_* (type int).
     */
    public const P_PAGE_SIZE = 'pageSize';

    /**
     * The spreadsheet title property (type string).
     */
    public const P_TITLE = 'title';

    /**
     * The user name property.
     */
    public const P_USER_NAME = 'userName';

    /**
     * The  Excel 97 (.xls) content type.
     */
    public const XLS_CONTENT_TYPE = 'application/vnd.ms-excel';

    /**
     * The  Excel 97 (.xls) writer type.
     */
    public const XLS_WRITER_TYPE = 'Xls';

    /**
     * The Office Open XML Excel 2007 (.xlsx) content type.
     */
    public const XLSX_CONTENT_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * The Office Open XML Excel 2007 (.xlsx) writer type.
     */
    public const XLSX_WRITER_TYPE = 'Xlsx';

    /**
     * The boolean format.
     *
     * @var string[]
     */
    private $booleanFormats = [];

    /**
     * The Spreadsheet.
     */
    private Spreadsheet $spreadsheet;

    /**
     * The file title.
     *
     * @var string
     */
    private $title;

    /**
     * The TranslatorInterface.
     */
    private TranslatorInterface $translator;

    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->spreadsheet = new Spreadsheet();
    }

    /**
     * Validate the spreed sheet title.
     *
     * @param string $title the title to validate
     *
     * @return string a valid title
     */
    public static function checkSheetTitle(string $title): string
    {
        // replace invalid characters
        $title = \str_replace(Worksheet::getInvalidCharacters(), '', $title);

        // check length
        if (StringHelper::countCharacters($title) > Worksheet::SHEET_TITLE_MAXIMUM_LENGTH) {
            $title = StringHelper::substring($title, 0, Worksheet::SHEET_TITLE_MAXIMUM_LENGTH);
        }

        return $title;
    }

    /**
     * Get the active sheet.
     */
    public function getActiveSheet(): Worksheet
    {
        return $this->spreadsheet->getActiveSheet();
    }

    /**
     * Gets the page setup of the active sheet.
     */
    public function getPageSetup(): PageSetup
    {
        return $this->getActiveSheet()->getPageSetup();
    }

    /**
     * Get the spreadsheet properties.
     */
    public function getProperties(): Properties
    {
        return $this->getSpreadsheet()->getProperties();
    }

    /**
     * Gets the spreadsheet.
     */
    public function getSpreadsheet(): Spreadsheet
    {
        return $this->spreadsheet;
    }

    /**
     * Sets the properties for the spreedsheet and the active sheet.
     *
     * @param array $properties the properties to set. One or more SpreadsheetService::P_* values
     */
    public function initialize(array $properties): self
    {
        if (isset($properties[self::P_TITLE])) {
            $this->setTitle($properties[self::P_TITLE]);
        }
        if (isset($properties[self::P_ACTIVE_TITLE])) {
            $this->setActiveTitle($properties[self::P_ACTIVE_TITLE]);
        }
        if (isset($properties[self::P_USER_NAME])) {
            $this->setUserName($properties[self::P_USER_NAME]);
        }
        if (isset($properties[self::P_COMPANY])) {
            $this->setCompany($properties[self::P_COMPANY]);
        }
        //->setKeywords("office 2007 openxml php")
        if (isset($properties[self::P_PAGE_SIZE])) {
            $this->setPageSize($properties[self::P_PAGE_SIZE]);
        } else {
            $this->setPageSizeA4();
        }
        if (isset($properties[self::P_MARGINS])) {
            $this->setMargins($properties[self::P_MARGINS]);
        } else {
            $this->setDefaultMargins();
        }
        if (isset($properties[self::P_LANDSCAPE]) && (bool) $properties[self::P_LANDSCAPE]) {
            $this->setPageLandscape();
        }
        if (isset($properties[self::P_GRIDLINE]) && (bool) $properties[self::P_GRIDLINE]) {
            $this->setPrintGridlines(true);
        }

        // header and footer
        $title = $properties[self::P_ACTIVE_TITLE] ?? null;
        $company = $properties[self::P_COMPANY] ?? null;
        $application = $properties[self::P_APPLICATION] ?? null;

        return $this->setHeaderFooter($title, $company, $application);
    }

    /**
     * Sets the title of the active sheet.
     */
    public function setActiveTitle(string $title): self
    {
        $title = self::checkSheetTitle($title);
        $this->getActiveSheet()->setTitle($title);

        return $this;
    }

    /**
     * Sets image at the given coordinate.
     *
     * @param string $path        the image path
     * @param string $coordinates the coordinates (eg: 'A1')
     * @param int    $width       the image width
     * @param int    $height      the image height
     */
    public function setCellImage(string $path, string $coordinates, int $width, int $height): self
    {
        $drawing = new Drawing();
        $drawing->setResizeProportional(false)
            ->setPath($path)
            ->setCoordinates($coordinates)
            ->setWidth($width)
            ->setHeight($height)
            ->setOffsetX(2)
            ->setOffsetY(2);

        $drawing->setWorksheet($this->getActiveSheet());

        // update size
        [$col, $row] = Coordinate::coordinateFromString($coordinates);
        $columnDimension = $this->getActiveSheet()->getColumnDimension($col);
        if ($width > $columnDimension->getWidth()) {
            $columnDimension->setWidth($width);
        }
        $rowDimension = $this->getActiveSheet()->getRowDimension((int) $row);
        if ($height > $rowDimension->getRowHeight()) {
            $rowDimension->setRowHeight($height);
        }

        return $this;
    }

    /**
     * Sets image at the given coordinate.
     *
     * @param string $path   the image path
     * @param int    $col    the column index (A = 1)
     * @param int    $row    the row index (1 = first row)
     * @param int    $width  the image width
     * @param int    $height the image height
     */
    public function setCellImageByColumnAndRow(string $path, int $col, int $row, int $width, int $height): self
    {
        $coordinates = $this->stringFromColumnAndRowIndex($col, $row);

        return $this->setCellImage($path, $coordinates, $width, $height);
    }

    /**
     * Sets the given format for the given column.
     *
     * @param int    $col    the column index (A = 1)
     * @param string $format the format to set
     */
    public function setColumnFormat(int $col, string $format): self
    {
        $sheet = $this->getActiveSheet();
        $name = $this->stringFromColumnIndex($col);
        $sheet->getStyle($name)->getNumberFormat()->setFormatCode($format);

        return $this;
    }

    /**
     * Sets the amount format ('#,##0.00') for the given column.
     *
     * @param int $col the column index (A = 1)
     */
    public function setColumnFormatAmount(int $col): self
    {
        return $this->setColumnFormat($col, NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    }

    /**
     * Sets the boolean format for the given column.
     *
     * @param int    $col        the column index (A = 1)
     * @param string $trueValue  the value to display when true
     * @param string $falseValue the value to display when false
     */
    public function setColumnFormatBoolean(int $col, string $trueValue, string $falseValue): self
    {
        $key = "$falseValue-$trueValue";
        if (!\array_key_exists($key, $this->booleanFormats)) {
            $trueValue = \str_replace('"', "''", $trueValue);
            $falseValue = \str_replace('"', "''", $falseValue);
            $format = "\"$trueValue\";;\"$falseValue\";";
            $this->booleanFormats[$key] = $format;
        } else {
            $format = $this->booleanFormats[$key];
        }

        return $this->setColumnFormat($col, $format);
    }

    /**
     * Sets the date format ('dd/mm/yyyy') for the given column.
     *
     * @param int $col the column index (A = 1)
     */
    public function setColumnFormatDate(int $col): self
    {
        return $this->setColumnFormat($col, NumberFormat::FORMAT_DATE_DDMMYYYY);
    }

    /**
     * Sets the date time format ('dd/mm/yyyy hh:mm') for the given column.
     *
     * @param int $col the column index (A = 1)
     */
    public function setColumnFormatDateTime(int $col): self
    {
        return $this->setColumnFormat($col, 'dd/mm/yyyy hh:mm');
    }

    /**
     * Sets the identifier format ('000000') for the given column.
     *
     * @param int $col the column index (A = 1)
     */
    public function setColumnFormatId(int $col): self
    {
        return $this->setColumnFormat($col, '000000');
    }

    /**
     * Sets the integer format ('#,##0') for the given column.
     *
     * @param int $col the column index (A = 1)
     */
    public function setColumnFormatInt(int $col): self
    {
        return $this->setColumnFormat($col, '#,##0');
    }

    /**
     * Sets the percent format ('0%')for the given column.
     *
     * @param int $col the column index (A = 1)
     */
    public function setColumnFormatPercent(int $col): self
    {
        return $this->setColumnFormat($col, NumberFormat::FORMAT_PERCENTAGE);
    }

    /**
     * Sets the percent format ('0.00%') for the given column.
     *
     * @param int $col the column index (A = 1)
     */
    public function setColumnFormatPercent00(int $col): self
    {
        return $this->setColumnFormat($col, NumberFormat::FORMAT_PERCENTAGE_00);
    }

    /**
     * Sets the 'Yes/No' boolean format ('"Yes";;"No";') for the given column.
     *
     * @param int $col the column index (A = 1)
     */
    public function setColumnFormatYesNo(int $col): self
    {
        $trueValue = $this->translator->trans('common.value_true');
        $falseValue = $this->translator->trans('common.value_false');

        return $this->setColumnFormatBoolean($col, $trueValue, $falseValue);
    }

    /**
     * Sets the company name property.
     *
     * @param string $company the company name
     */
    public function setCompany(?string $company): self
    {
        if ($company) {
            $this->getProperties()->setCompany($company);
        }

        return $this;
    }

    /**
     * Sets the margins of the active sheet to default value (10 millimeters).
     */
    public function setDefaultMargins(): self
    {
        return $this->setMargins(self::DEFAULT_MARGIN);
    }

    /**
     * Sets the header and footer texts.
     *
     * The header contains:
     * <ul>
     * <li>The title on the left with bold style.</li>
     * <li>The company name on the right with bold style.</li>
     * </ul>
     * The footer contains:
     * <ul>
     * <li>The current page and the total pages on the left.</li>
     * <li>The application name on the center.</li>
     * <li>The date and the time on the right.</li>
     * </ul>
     *
     * @param string $title       the title
     * @param string $company     the company name
     * @param string $application the application name
     */
    public function setHeaderFooter(?string $title, ?string $company, ?string $application): self
    {
        $header = '';
        if ($title) {
            $header .= '&L&B' . $this->cleanHeaderFooter($title);
        }
        if ($company) {
            $header .= '&R&B' . $this->cleanHeaderFooter($company);
        }

        $footer = '&LPage &P / &N'; // pages
        if ($application) {
            $footer .= '&C' . $this->cleanHeaderFooter($application);
        }
        $footer .= '&R&D - &T'; // date and time

        $pageMargins = $this->getActiveSheet()->getPageMargins();
        $headerFooter = $this->getActiveSheet()->getHeaderFooter();
        if (!empty($header)) {
            $pageMargins->setTop(self::HEADER_FOOTER_MARGIN);
            $headerFooter->setOddHeader($header);
        }
        if (!empty($footer)) {
            $pageMargins->setBottom(self::HEADER_FOOTER_MARGIN);
            $headerFooter->setOddFooter($footer);
        }

        return $this;
    }

    /**
     * Sets the headers of the active sheet with bold style and freezed first row.
     *
     * @param array $headers the headers where key is the text to translate and value is the
     *                       horizontal alignment or if an array, the horizontal and vertical
     *                       alignments
     */
    public function setHeaderValues(array $headers): self
    {
        $sheet = $this->getActiveSheet();

        $col = 1;
        foreach ($headers as $id => $alignment) {
            $name = $this->stringFromColumnIndex($col);
            $sheet->getColumnDimension($name)->setAutoSize(true);
            $sheet->setCellValue("{$name}1", $this->translator->trans($id));

            if (\is_array($alignment)) {
                $sheet->getStyle($name)->getAlignment()
                    ->setHorizontal($alignment[0])
                    ->setVertical($alignment[1]);
                $sheet->getStyle("{$name}1")
                    ->getAlignment()
                    ->setHorizontal($alignment[0])
                    ->setVertical($alignment[1]);
            } else {
                $sheet->getStyle($name)->getAlignment()
                    ->setHorizontal($alignment);
                $sheet->getStyle("{$name}1")->getAlignment()
                    ->setHorizontal($alignment);
            }

            ++$col;
        }

        $name = $this->stringFromColumnIndex(\count($headers));
        $sheet->getStyle('A1:' . $name . '1')->getFont()->setBold(true);
        $sheet->freezePane('A2');

        $sheet->getPageSetup()
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setHorizontalCentered(true)
            ->setRowsToRepeatAtTopByStartAndEnd(1, 1);

        return $this;
    }

    /**
     * Sets the margins of the the active sheet.
     *
     * @param float $margins the margins to set
     */
    public function setMargins(float $margins): self
    {
        $pageMargins = $this->getActiveSheet()->getPageMargins();
        $pageMargins->setTop($margins)
            ->setBottom($margins)
            ->setLeft($margins)
            ->setRight($margins);

        return $this;
    }

    /**
     * Sets the orientation of the active sheet to landscape.
     */
    public function setPageLandscape(): self
    {
        $this->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);

        return $this;
    }

    /**
     * Sets the paper size for the active sheet.
     *
     * @param int $size the paper size that must be one of PageSetup::PAPERSIZE_*
     */
    public function setPageSize(int $size): self
    {
        $this->getPageSetup()->setPaperSize($size);

        return $this;
    }

    /**
     * Sets the paper size to A4 for the active sheet.
     */
    public function setPageSizeA4(): self
    {
        return $this->setPageSize(PageSetup::PAPERSIZE_A4);
    }

    /**
     * Set a value indicating if the gridlines are printed.
     *
     * @param bool $printGridlines true to print the gridlines
     */
    public function setPrintGridlines(bool $printGridlines): self
    {
        $this->getActiveSheet()->setPrintGridlines($printGridlines);

        return $this;
    }

    /**
     * Sets the values of the given row.
     *
     * @param int   $row    the row index (first row  = 1)
     * @param array $values the values to set
     * @param int   $col    the starting column index (A = 1)
     */
    public function setRowValues(int $row, array $values, int $col = 1): self
    {
        $sheet = $this->getActiveSheet();
        foreach ($values as $value) {
            if (null !== $value) {
                if ($value instanceof \DateTimeInterface) {
                    $value = Date::PHPToExcel($value);
                } elseif (\is_bool($value)) {
                    $value = $value ? 1 : 0;
                }
                $sheet->setCellValueByColumnAndRow($col, $row, $value);
            }
            ++$col;
        }

        return $this;
    }

    /**
     * Sets selected cell of the active sheet.
     *
     * @param string $cell the cell coordinate (i.e. 'A1')
     */
    public function setSelectedCell(string $cell): self
    {
        $this->getActiveSheet()->setSelectedCell($cell);

        return $this;
    }

    /**
     * Sets the file title.
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;
        if ($this->title) {
            $this->getProperties()->setTitle($this->title);
        }

        return $this;
    }

    /**
     * Sets the user name for the creator and the last modified properties.
     *
     * @param string $userName the user name
     */
    public function setUserName(?string $userName): self
    {
        if ($userName) {
            $this->getProperties()
                ->setCreator($userName)
                ->setLastModifiedBy($userName);
        }

        return $this;
    }

    /**
     * Get the string from the given column and row index (eg. 2,10 => 'B10').
     *
     * @param int $columnIndex the column index (A = 1)
     * @param int $rowIndex    the row index (First = 1)
     */
    public function stringFromColumnAndRowIndex(int $columnIndex, int $rowIndex): string
    {
        $columnName = $this->stringFromColumnIndex($columnIndex);

        return $columnName . $rowIndex;
    }

    /**
     * Get the string from the given column index.
     *
     * @param int $columnIndex the column index (A = 1)
     */
    public function stringFromColumnIndex(int $columnIndex): string
    {
        return Coordinate::stringFromColumnIndex($columnIndex);
    }

    /**
     * Gets the streamed response for this spread sheet with the Excel 97 (.xls) format.
     *
     * @param bool $inline <code>true</code> to send the file inline to the browser. The viewer is used if available.
     *                     <code>false</code> to send to the browser and force a file download.
     */
    public function xlsResponse(bool $inline = true): StreamedResponse
    {
        return $this->streamResponse(self::XLS_WRITER_TYPE, self::XLS_CONTENT_TYPE, $inline);
    }

    /**
     * Gets the streamed response for this spread sheet with the Office Open XML Excel 2007 (.xlsx) format.
     *
     * @param bool $inline <code>true</code> to send the file inline to the browser. The viewer is used if available.
     *                     <code>false</code> to send to the browser and force a file download.
     */
    public function xlsxResponse(bool $inline = true): StreamedResponse
    {
        return $this->streamResponse(self::XLSX_WRITER_TYPE, self::XLSX_CONTENT_TYPE, $inline);
    }

    /**
     * Clean a header/footer property.
     *
     * @param string $value the property to clean
     */
    private function cleanHeaderFooter(string $value): string
    {
        return \str_replace('&', '&&', $value);
    }

    /**
     * Gets a streamed response.
     *
     * @param string $writerType  the writer type
     * @param string $contentType the content type
     * @param bool   $inline      <code>true</code> to send the file inline to the browser. The viewer is used if available.
     *                            <code>false</code> to send to the browser and force a file download.
     */
    private function streamResponse(string $writerType, string $contentType, bool $inline): StreamedResponse
    {
        $title = $this->title ?? 'export';
        $name = $title . '.' . \strtolower($writerType);
        $encoded = (new UnicodeString($name))->ascii()->toString();
        $writer = IOFactory::createWriter($this->getSpreadsheet(), $writerType);
        $disposition = $inline ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT;

        $callback = function () use ($writer): void {
            $writer->save('php://output');
        };

        $headers = [
            'Pragma' => 'public',
            'Content-Type' => $contentType,
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $name, $encoded),
        ];

        return new StreamedResponse($callback, StreamedResponse::HTTP_OK, $headers);
    }
}
