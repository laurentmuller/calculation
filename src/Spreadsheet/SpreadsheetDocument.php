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

namespace App\Spreadsheet;

use App\Controller\AbstractController;
use App\Model\CustomerInformation;
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
 */
class SpreadsheetDocument extends Spreadsheet
{
    use TranslatorTrait;

    /**
     * The default margins (10 millimeters).
     */
    final public const DEFAULT_MARGIN = 0.4;

    /**
     * The top margins when customer header is present (21 millimeters).
     */
    final public const HEADER_CUSTOMER_MARGIN = 0.83;

    /**
     * The top and bottom margins when header and/or footer is present (12 millimeters).
     */
    final public const HEADER_FOOTER_MARGIN = 0.47;

    /**
     * The date time format ('dd/mm/yyyy hh:mm').
     */
    private const FORMAT_DATE_TIME = 'dd/mm/yyyy hh:mm';

    /**
     * The identifier format ('000000').
     */
    private const FORMAT_ID = '000000';

    /**
     * The integer format ('#,##0').
     */
    private const FORMAT_INT = '#,##0';

    /**
     * The file title.
     */
    protected ?string $title = null;

    /**
     * The boolean formats.
     *
     * @var string[]
     */
    private array $booleanFormats = [];

    /**
     * Constructor.
     */
    public function __construct(private readonly TranslatorInterface $translator)
    {
        parent::__construct();
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
        /** @var string[] $invalidChars */
        $invalidChars = Worksheet::getInvalidCharacters();
        $title = \str_replace($invalidChars, '', $title);

        // check length
        if (StringHelper::countCharacters($title) > Worksheet::SHEET_TITLE_MAXIMUM_LENGTH) {
            return StringHelper::substring($title, 0, Worksheet::SHEET_TITLE_MAXIMUM_LENGTH);
        }

        return $title;
    }

    /**
     * Create worksheet, set title and add it to this workbook. The created sheet is activated.
     *
     * @param ?string $title      the title of the worksheet
     * @param ?int    $sheetIndex the  index where worksheet should go (0,1,..., or null for last)
     *
     * @return Worksheet the newly created worksheet
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function createSheetAndTitle(AbstractController $controller, string $title = null, int $sheetIndex = null): Worksheet
    {
        $sheet = parent::createSheet($sheetIndex);
        if (null !== $title) {
            $sheet->setTitle(self::checkSheetTitle($title));
        }
        $this->setActiveSheetIndex($sheetIndex ?? $this->getSheetCount() - 1);
        $customer = $controller->getUserService()->getCustomer();
        $this->setHeaderFooter($title, $customer)
            ->setPrintGridlines(true);

        return $sheet;
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
     * {@inheritDoc}
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Initialize this service.
     *
     * @param AbstractController $controller the controller to get properties
     * @param string             $title      the spreadsheet title to translate
     * @param bool               $landscape  true to set landscape orientation, false for default (portrait)
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function initialize(AbstractController $controller, string $title, bool $landscape = false): self
    {
        $customer = $controller->getUserService()->getCustomer();
        $application = $controller->getApplicationName();
        $username = $controller->getUserIdentifier();
        $title = $this->trans($title);

        $this->setHeaderFooter($title, $customer)
            ->setTitle($title)
            ->setActiveTitle($title)
            ->setCompany($customer->getName())
            ->setUserName($username)
            ->setCategory($application)
            ->setPrintGridlines(true);

        if ($landscape) {
            return $this->setPageLandscape();
        }

        return $this;
    }

    /**
     * Set merge on a cell range by using cell coordinates.
     *
     * @param int  $startColumn the index of the first column (A = 1)
     * @param int  $endColumn   the index of the last column
     * @param int  $startRow    the index of first row (1 = First row)
     * @param ?int $endRow      the index of the last cell or null to use the start row
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception if an exception occurs
     */
    public function mergeCells(int $startColumn, int $endColumn, int $startRow, ?int $endRow = null): static
    {
        $this->getActiveSheet()->mergeCells([$startColumn, $startRow, $endColumn, $endRow ?? $startRow]);

        return $this;
    }

    /**
     * Sets the title of the active sheet. If the controller is not null,
     * the header and footer are also updated.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function setActiveTitle(string $title, ?AbstractController $controller = null): self
    {
        $title = self::checkSheetTitle($title);
        $this->getActiveSheet()->setTitle($title);
        if (null !== $controller) {
            $customer = $controller->getUserService()->getCustomer();
            $this->setHeaderFooter($title, $customer);
        }

        return $this;
    }

    /**
     * Set the auto-sizing behavior for the given column.
     *
     * @param int  $columnIndex the column index (A = 1)
     * @param bool $autoSize    true to auto-sizing; false if not
     */
    public function setAutoSize(int $columnIndex, bool $autoSize = true): static
    {
        $sheet = $this->getActiveSheet();
        $name = $this->stringFromColumnIndex($columnIndex);
        $sheet->getColumnDimension($name)->setAutoSize($autoSize);

        return $this;
    }

    /**
     * Sets the category property.
     *
     * @param ?string $category the category
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
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception if an exception occurs
     */
    public function setCellImage(string $path, string $coordinates, int $width, int $height): self
    {
        $sheet = $this->getActiveSheet();

        $drawing = new Drawing();
        $drawing->setPath($path)
            ->setResizeProportional(false)
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
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception if an exception occurs
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
    public function setCellValue(Worksheet $sheet, int $columnIndex, int $rowIndex, mixed $value): self
    {
        if (null !== $value && '' !== $value) {
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
     * @param int $columnIndex the column index (A = 1)
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
     * @param int  $columnIndex the column index (A = 1)
     * @param int  $width       the width to set
     * @param bool $wrapText    true to wrap text
     *
     * @see SpreadsheetDocument::setWrapText()
     */
    public function setColumnWidth(int $columnIndex, int $width, bool $wrapText = false): static
    {
        $sheet = $this->getActiveSheet();
        $name = $this->stringFromColumnIndex($columnIndex);
        $sheet->getColumnDimension($name)->setWidth($width);

        return $wrapText ? $this->setWrapText($columnIndex) : $this;
    }

    /**
     * Sets the company name property.
     *
     * @param ?string $company the company name
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
            $sheet->getStyle("{$name}1")->getFont()->getColor()
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
        return $this->setFormat($columnIndex, NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
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
        return $this->setFormat($columnIndex, self::FORMAT_DATE_TIME);
    }

    /**
     * Sets the identifier format ('000000') for the given column.
     *
     * @param int $columnIndex the column index (A = 1)
     */
    public function setFormatId(int $columnIndex): self
    {
        return $this->setFormat($columnIndex, self::FORMAT_ID);
    }

    /**
     * Sets the integer format ('#,##0') for the given column.
     *
     * @param int $columnIndex the column index (A = 1)
     */
    public function setFormatInt(int $columnIndex): self
    {
        return $this->setFormat($columnIndex, self::FORMAT_INT);
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
     * Sets the price format ('#,##0.00') for the given column and with the red color when value is equal to 0.
     *
     * @param int $columnIndex the column index (A = 1)
     */
    public function setFormatPrice(int $columnIndex): self
    {
        $format = NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;

        return $this->setFormat($columnIndex, "[Red][<=0]$format;$format");
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
     */
    public function setHeaderFooter(?string $title, CustomerInformation $customer): self
    {
        $sheet = $this->getActiveSheet();
        $pageMargins = $sheet->getPageMargins();

        $header = new HeaderFooter(true, 9);
        if ($customer->isPrintAddress()) {
            $header->addLeft($customer->getName() ?? '', true)
                ->addLeft($customer->getAddress() ?? '')
                ->addLeft($customer->getZipCity() ?? '')
                ->addCenter($title ?? '', true)
                ->addRight($customer->getTranslatedPhone($this))
                ->addRight($customer->getTranslatedFax($this))
                ->addRight($customer->getEmail() ?? '');
            $pageMargins->setTop(self::HEADER_CUSTOMER_MARGIN);
        } else {
            $header->addLeft($title ?? '', true)
                ->addRight($customer->getName() ?? '', true);
            $pageMargins->setTop(self::HEADER_FOOTER_MARGIN);
        }
        $header->apply($sheet);

        $pageMargins->setBottom(self::HEADER_FOOTER_MARGIN);
        $footer = new HeaderFooter(false, 9);
        $footer->addPages()->addDateTime()
            ->apply($sheet);

        return $this;
    }

    /**
     * Sets the headers of the active sheet with bold style and frozen first row.
     *
     * @param array $headers     the headers where key is the text to translate and value is the
     *                           horizontal alignment or if is an array, the horizontal and the vertical
     *                           alignments
     * @param int   $columnIndex the starting column index (A = 1)
     * @param int   $rowIndex    the row index (1 = First row)
     * @psalm-param array<string, string|string[]> $headers
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception if an exception occurs
     */
    public function setHeaderValues(array $headers, int $columnIndex = 1, int $rowIndex = 1): self
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
            $sheet->setCellValue("$name$rowIndex", $this->trans($id));
        }

        $firstName = $this->stringFromColumnIndex($columnIndex);
        $lastName = $this->stringFromColumnIndex($columnIndex + \count($headers) - 1);
        $sheet->getStyle("$firstName$rowIndex:$lastName$rowIndex")->getFont()->setBold(true);
        $sheet->freezePane('A' . ($rowIndex + 1));

        $sheet->getPageSetup()
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setHorizontalCentered(true)
            ->setRowsToRepeatAtTopByStartAndEnd($rowIndex, $rowIndex);

        return $this;
    }

    /**
     * Set the manager property.
     */
    public function setManager(?string $manager): self
    {
        if ($manager) {
            $this->getProperties()->setManager($manager);
        }

        return $this;
    }

    /**
     * Sets the margins of the active sheet.
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
     * Sets the page break at the given row.
     *
     * @param int $row the row index (1 = First row)
     */
    public function setPageBreak(int $row): self
    {
        $this->getActiveSheet()->setBreakByColumnAndRow(1, $row, Worksheet::BREAK_ROW);

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
     * @param int $size the paper size that must be one of PageSetup paper size constant
     * @psalm-param PageSetup::PAPERSIZE_* $size
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
        /** @psalm-var mixed $value*/
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
    public function setSelectedCell(string $coordinates): static
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
     * @param ?string $subject the subject
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
     * Sets the username for the creator and the last modified properties.
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
     * Set wrap text for the given column. The auto-size is automatically disabled.
     *
     * @param int $columnIndex the column index (A = 1)
     */
    public function setWrapText(int $columnIndex): static
    {
        $sheet = $this->getActiveSheet();
        $name = $this->stringFromColumnIndex($columnIndex);
        $sheet->getColumnDimension($name)->setAutoSize(false);
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
}
