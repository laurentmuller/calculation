<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Excel;

use App\Controller\AbstractController;
use App\Traits\TranslatorTrait;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extends the Spreadsheet class with shortcuts to set properties, formats and values.
 *
 * @author Laurent Muller
 */
class ExcelDocument extends Spreadsheet
{
    use TranslatorTrait;

    /**
     * The default margins (0.4" = 10 millimeters).
     */
    public const DEFAULT_MARGIN = 0.4;

    /**
     * The top and bottom margins when header and/or footer is present (0.51" = 13 millimeters).
     */
    public const HEADER_FOOTER_MARGIN = 0.51;

    /**
     * The file title.
     */
    protected ?string $title = null;

    /**
     * The boolean format.
     *
     * @var string[]
     */
    private array $booleanFormats = [];

    /**
     * Constructor.
     *
     * @param TranslatorInterface $translator the translator used for the title, the headers and the boolean formats
     */
    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct();
        $this->translator = $translator;
        $this->setPageSize(PageSetup::PAPERSIZE_A4);
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
            return StringHelper::substring($title, 0, Worksheet::SHEET_TITLE_MAXIMUM_LENGTH);
        }

        return $title;
    }

    /**
     * Gets the amount format.
     */
    public function getAmountFormat(): string
    {
        return NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;
    }

    /**
     * Gets the page setup of the active sheet.
     */
    public function getPageSetup(): PageSetup
    {
        return $this->getActiveSheet()->getPageSetup();
    }

    /**
     * Gets the percent format.
     *
     * @param bool $decimals true to display 2 decimals ('0.00%'), false if none ('0%').
     */
    public function getPercentFormat(bool $decimals = false): string
    {
        if ($decimals) {
            return NumberFormat::FORMAT_PERCENTAGE_00;
        }

        return NumberFormat::FORMAT_PERCENTAGE;
    }

    /**
     * Gets the title.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Initialize this service.
     *
     * @param AbstractController $controller the controller to get properties
     * @param string             $title      the spread sheet title to translate
     * @param bool               $landscape  true to set landscape orientation, false for default (portrait)
     */
    public function initialize(AbstractController $controller, string $title, bool $landscape = false): self
    {
        $company = $controller->getApplication()->getCustomerName();
        $application = $controller->getApplicationName();
        $username = $controller->getUserName();
        $title = $this->trans($title);

        $this->setHeaderFooter($title, $company, $application)
            ->setTitle($title)
            ->setActiveTitle($title)
            ->setCompany($company)
            ->setUserName($username)
            ->setCategory($application)
            ->setPrintGridlines(true);

        if ($landscape) {
            return $this->setPageLandscape();
        }

        return $this;
    }

    /**
     * Sets the title of the active sheet.
     */
    public function setActiveTitle(string $title): self
    {
        $this->getActiveSheet()->setTitle(self::checkSheetTitle($title));

        return $this;
    }

    /**
     * Set the auto-sizing behavior for the given column.
     *
     * @param int  $columnIndex the column index (A = 1)
     * @param bool $autoSize    true to auto-sizing; false if not
     */
    public function setAutoSize(int $columnIndex, bool $autoSize = true): self
    {
        $sheet = $this->getActiveSheet();
        $name = $this->stringFromColumnIndex($columnIndex);
        $sheet->getColumnDimension($name)->setAutoSize($autoSize);

        return $this;
    }

    /**
     * Sets the category property.
     *
     * @param string $category the category
     */
    public function setCategory(?string $category): self
    {
        if ($category) {
            $this->getProperties()->setCategory($category);
        }

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
        $sheet = $this->getActiveSheet();

        $drawing = new Drawing();
        $drawing->setResizeProportional(false)
            ->setPath($path)
            ->setCoordinates($coordinates)
            ->setWidth($width)
            ->setHeight($height)
            ->setOffsetX(2)
            ->setOffsetY(2)
            ->setWorksheet($sheet);

        // update size
        [$columnIndex, $rowIndex] = Coordinate::coordinateFromString($coordinates);
        $columnDimension = $sheet->getColumnDimension($columnIndex);
        if ($width > $columnDimension->getWidth()) {
            $columnDimension->setWidth($width);
        }
        $rowDimension = $sheet->getRowDimension((int) $rowIndex);
        if ($height > $rowDimension->getRowHeight()) {
            $rowDimension->setRowHeight($height);
        }

        return $this;
    }

    /**
     * Sets image at the given coordinate.
     *
     * @param string $path        the image path
     * @param int    $columnIndex the column index (A = 1)
     * @param int    $rowIndex    the row index (1 = First row)
     * @param int    $width       the image width
     * @param int    $height      the image height
     */
    public function setCellImageByColumnAndRow(string $path, int $columnIndex, int $rowIndex, int $width, int $height): self
    {
        $coordinates = $this->stringFromColumnAndRowIndex($columnIndex, $rowIndex);

        return $this->setCellImage($path, $coordinates, $width, $height);
    }

    /**
     * Set a cell value by using numeric cell coordinates.
     *
     * @param Worksheet $sheet       the active work sheet
     * @param int       $columnIndex the column index of the cell (A = 1)
     * @param int       $rowIndex    the row index of the cell (1 = First row)
     * @param mixed     $value       the value of the cell
     */
    public function setCellValue(Worksheet $sheet, int $columnIndex, int $rowIndex, $value): self
    {
        if (null !== $value) {
            if ($value instanceof \DateTimeInterface) {
                $value = Date::PHPToExcel($value);
            } elseif (\is_bool($value)) {
                $value = $value ? 1 : 0;
            }
            $sheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $value);
        }

        return $this;
    }

    /**
     * Add conditionals to the given column.
     *
     * @param int         $columnIndex     the column index (A = 1)
     * @param Conditional ...$conditionals the conditionals to add
     */
    public function setColumnConditional(int $columnIndex, Conditional ...$conditionals): self
    {
        $sheet = $this->getActiveSheet();
        $name = $this->stringFromColumnIndex($columnIndex);
        $style = $sheet->getStyle($name);
        $existingConditionals = \array_merge($style->getConditionalStyles(), $conditionals);
        $style->setConditionalStyles($existingConditionals);

        return $this;
    }

    /**
     * Set the width for the given column.
     *
     * @param int $columnIndex the column index (A = 1)
     * @param int $width       the width to set
     */
    public function setColumnWidth(int $columnIndex, int $width): self
    {
        $sheet = $this->getActiveSheet();
        $name = $this->stringFromColumnIndex($columnIndex);
        $sheet->getColumnDimension($name)->setWidth($width);

        return $this;
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
     * Sets the foreground color for the given column.
     *
     * @param int    $columnIndex   the column index (A = 1)
     * @param string $color         the hexadecimal color or an empty string ('') for black color
     * @param bool   $includeHeader true to set color for all rows; false to skip the first row
     */
    public function setForeground(int $columnIndex, string $color, bool $includeHeader = false): self
    {
        $sheet = $this->getActiveSheet();
        $name = $this->stringFromColumnIndex($columnIndex);
        $style = $sheet->getStyle($name)->getFont()->getColor();

        if (\strlen($color) > 6) {
            $style->setARGB($color);
        } else {
            $style->setRGB($color);
        }

        if (!$includeHeader) {
            $style = $sheet->getStyle("{$name}1")->getFont()->getColor()
                ->setARGB(Color::COLOR_BLACK);
        }

        return $this;
    }

    /**
     * Sets the format for the given column.
     *
     * @param int    $columnIndex the column index (A = 1)
     * @param string $format      the format to set
     */
    public function setFormat(int $columnIndex, string $format): self
    {
        $sheet = $this->getActiveSheet();
        $name = $this->stringFromColumnIndex($columnIndex);
        $sheet->getStyle($name)->getNumberFormat()->setFormatCode($format);

        return $this;
    }

    /**
     * Sets the amount format ('#,##0.00') for the given column.
     *
     * @param int $columnIndex the column index (A = 1)
     */
    public function setFormatAmount(int $columnIndex): self
    {
        return $this->setFormat($columnIndex, $this->getAmountFormat());
    }

    /**
     * Sets the boolean format for the given column.
     *
     * @param int    $columnIndex the column index (A = 1)
     * @param string $true        the value to display when <code>true</code>
     * @param string $false       the value to display when <code>false</code>
     * @param bool   $translate   <code>true</code> to translate values
     */
    public function setFormatBoolean(int $columnIndex, string $true, string $false, bool $translate = false): self
    {
        $key = "$false-$true";
        if (!\array_key_exists($key, $this->booleanFormats)) {
            if ($translate) {
                $true = $this->trans($true);
                $false = $this->trans($false);
            }
            $true = \str_replace('"', "''", $true);
            $false = \str_replace('"', "''", $false);
            $format = "\"$true\";;\"$false\";@";
            $this->booleanFormats[$key] = $format;
        } else {
            $format = $this->booleanFormats[$key];
        }

        return $this->setFormat($columnIndex, $format);
    }

    /**
     * Sets the date format ('dd/mm/yyyy') for the given column.
     *
     * @param int $columnIndex the column index (A = 1)
     */
    public function setFormatDate(int $columnIndex): self
    {
        return $this->setFormat($columnIndex, NumberFormat::FORMAT_DATE_DDMMYYYY);
    }

    /**
     * Sets the date time format ('dd/mm/yyyy hh:mm') for the given column.
     *
     * @param int $columnIndex the column index (A = 1)
     */
    public function setFormatDateTime(int $columnIndex): self
    {
        return $this->setFormat($columnIndex, 'dd/mm/yyyy hh:mm');
    }

    /**
     * Sets the identifier format ('000000') for the given column.
     *
     * @param int $columnIndex the column index (A = 1)
     */
    public function setFormatId(int $columnIndex): self
    {
        return $this->setFormat($columnIndex, '000000');
    }

    /**
     * Sets the integer format ('#,##0') for the given column.
     *
     * @param int $columnIndex the column index (A = 1)
     */
    public function setFormatInt(int $columnIndex): self
    {
        return $this->setFormat($columnIndex, '#,##0');
    }

    /**
     * Sets the percent format for the given column.
     *
     * @param int  $columnIndex the column index (A = 1)
     * @param bool $decimals    true to display 2 decimals ('0.00%'), false if none ('0%').
     */
    public function setFormatPercent(int $columnIndex, bool $decimals = false): self
    {
        return $this->setFormat($columnIndex, $this->getPercentFormat($decimals));
    }

    /**
     * Sets the translated 'Yes/No' boolean format for the given column.
     *
     * @param int $columnIndex the column index (A = 1)
     */
    public function setFormatYesNo(int $columnIndex): self
    {
        return $this->setFormatBoolean($columnIndex, 'common.value_true', 'common.value_false', true);
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

        $sheet = $this->getActiveSheet();
        $pageMargins = $sheet->getPageMargins();
        $headerFooter = $sheet->getHeaderFooter();
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
     * @param array $headers     the headers where key is the text to translate and value is the
     *                           horizontal alignment or if is an array, the horizontal and the vertical
     *                           alignments
     * @param int   $columnIndex the starting column index (A = 1)
     */
    public function setHeaderValues(array $headers, int $columnIndex = 1): self
    {
        $sheet = $this->getActiveSheet();

        $index = $columnIndex;
        foreach ($headers as $id => $alignment) {
            $name = $this->stringFromColumnIndex($index++);
            if (\is_array($alignment)) {
                $sheet->getStyle($name)
                    ->getAlignment()
                    ->setHorizontal($alignment[0])
                    ->setVertical($alignment[1]);
            } else {
                $sheet->getStyle($name)
                    ->getAlignment()
                    ->setHorizontal($alignment);
            }
            $sheet->getColumnDimension($name)->setAutoSize(true);
            $sheet->setCellValue("{$name}1", $this->trans($id));
        }

        $firstName = $this->stringFromColumnIndex($columnIndex);
        $lastName = $this->stringFromColumnIndex($columnIndex + \count($headers) - 1);
        $sheet->getStyle("{$firstName}1:{$lastName}1")->getFont()->setBold(true);
        $sheet->freezePane('A2');

        $sheet->getPageSetup()
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setHorizontalCentered(true)
            ->setRowsToRepeatAtTopByStartAndEnd(1, 1);

        return $this;
    }

    /**
     * Set the manager property.
     *
     * @param string $manager the manager
     */
    public function setManager(?string $manager): self
    {
        if ($manager) {
            $this->getProperties()->setManager($manager);
        }

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
     * Sets the orientation of the active sheet to portrait.
     */
    public function setPagePortrait(): self
    {
        $this->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);

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
     * @param int   $rowIndex    the row index (1 = First row)
     * @param array $values      the values to set
     * @param int   $columnIndex the starting column index (A = 1)
     */
    public function setRowValues(int $rowIndex, array $values, int $columnIndex = 1): self
    {
        $sheet = $this->getActiveSheet();
        foreach ($values as $value) {
            $this->setCellValue($sheet, $columnIndex++, $rowIndex, $value);
        }

        return $this;
    }

    /**
     * Sets selected cell of the active sheet.
     *
     * @param string $coordinates the cell coordinate (i.e. 'A1')
     */
    public function setSelectedCell(string $coordinates): self
    {
        $this->getActiveSheet()->setSelectedCell($coordinates);

        return $this;
    }

    /**
     * Sets selected cell of the active sheet.
     *
     * @param int $columnIndex the column index (A = 1)
     * @param int $rowIndex    the row index (1 = First row)
     */
    public function setSelectedCellByColumnAndRow(int $columnIndex, int $rowIndex): self
    {
        $coordinates = $this->stringFromColumnAndRowIndex($columnIndex, $rowIndex);

        return $this->setSelectedCell($coordinates);
    }

    /**
     * Sets the shrink to fit for the given column.
     *
     * @param int $columnIndex the column index (A = 1)
     */
    public function setShrinkToFit(int $columnIndex): self
    {
        $sheet = $this->getActiveSheet();
        $name = $this->stringFromColumnIndex($columnIndex);
        $sheet->getStyle($name)->getAlignment()->setShrinkToFit(true);

        return $this;
    }

    /**
     * Sets the subject property.
     *
     * @param string $subject the subject
     */
    public function setSubject(?string $subject): self
    {
        if ($subject) {
            $this->getProperties()->setSubject($subject);
        }

        return $this;
    }

    /**
     * Sets the file title.
     */
    public function setTitle(?string $title): self
    {
        $this->title = $title;
        if ($title) {
            $this->getProperties()->setTitle($title);
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
     * Set wrap text for the given column.
     *
     * @param int $columnIndex the column index (A = 1)
     */
    public function setWrapText(int $columnIndex): self
    {
        $sheet = $this->getActiveSheet();
        $name = $this->stringFromColumnIndex($columnIndex);
        $sheet->getStyle($name)->getAlignment()->setWrapText(true);

        return $this;
    }

    /**
     * Get the string coordinate from the given column and row index (eg. 2,10 => 'B10').
     *
     * @param int $columnIndex the column index (A = 1)
     * @param int $rowIndex    the row index (1 = First row)
     */
    public function stringFromColumnAndRowIndex(int $columnIndex, int $rowIndex): string
    {
        $columnName = $this->stringFromColumnIndex($columnIndex);

        return $columnName . $rowIndex;
    }

    /**
     * Get the string from the given column index.
     *
     * @param int $columnIndex the column index (1 = A)
     */
    public function stringFromColumnIndex(int $columnIndex): string
    {
        return Coordinate::stringFromColumnIndex($columnIndex);
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
}
