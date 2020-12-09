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
use App\Util\Utils;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extends the Spreadsheet class with shortcuts to set properties, formats and values.
 *
 * @author Laurent Muller
 */
class ExcelDocument extends Spreadsheet
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
     * The Microsoft Excel (OpenXML) mime type.
     */
    public const MIME_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * The TranslatorInterface.
     */
    protected TranslatorInterface $translator;

    /**
     * The boolean format.
     *
     * @var string[]
     */
    private $booleanFormats = [];

    /**
     * The file title.
     *
     * @var string
     */
    private $title;

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
     * Gets the output headers.
     *
     * @param bool   $inline <code>true</code> to send the file inline to the browser. The Spreasheet viewer is used if available.
     *                       <code>false</code> to send to the browser and force a file download with the name given.
     * @param string $name   the name of the document file or <code>''</code> to use the default name ('document.xlsx')
     *
     * @return string[] the output headers
     *
     * @see ExcelResponse
     */
    public function getOutputHeaders(bool $inline = true, string $name = ''): array
    {
        $name = empty($name) ? 'document.xlsx' : \basename($name);
        $encoded = Utils::ascii($name);

        if ($inline) {
            $type = self::MIME_TYPE;
            $disposition = HeaderUtils::DISPOSITION_INLINE;
        } else {
            $type = 'application/x-download';
            $disposition = HeaderUtils::DISPOSITION_ATTACHMENT;
        }

        return [
            'Pragma' => 'public',
            'Content-Type' => $type,
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $name, $encoded),
        ];
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
        $title = $this->translator->trans($title);
        $username = $controller->getUserName();

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
     * Sets the format for the given column.
     *
     * @param int    $col    the column index (A = 1)
     * @param string $format the format to set
     */
    public function setFormat(int $col, string $format): self
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
    public function setFormatAmount(int $col): self
    {
        return $this->setFormat($col, NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    }

    /**
     * Sets the boolean format for the given column.
     *
     * @param int    $col       the column index (A = 1)
     * @param string $true      the value to display when <code>true</code>
     * @param string $false     the value to display when <code>false</code>
     * @param bool   $translate <code>true</code> to translate values
     */
    public function setFormatBoolean(int $col, string $true, string $false, bool $translate = false): self
    {
        $key = "$false-$true";
        if (!\array_key_exists($key, $this->booleanFormats)) {
            if ($translate) {
                $true = $this->translator->trans($true);
                $false = $this->translator->trans($false);
            }
            $true = \str_replace('"', "''", $true);
            $false = \str_replace('"', "''", $false);
            $format = "\"$true\";;\"$false\";";
            $this->booleanFormats[$key] = $format;
        } else {
            $format = $this->booleanFormats[$key];
        }

        return $this->setFormat($col, $format);
    }

    /**
     * Sets the date format ('dd/mm/yyyy') for the given column.
     *
     * @param int $col the column index (A = 1)
     */
    public function setFormatDate(int $col): self
    {
        return $this->setFormat($col, NumberFormat::FORMAT_DATE_DDMMYYYY);
    }

    /**
     * Sets the date time format ('dd/mm/yyyy hh:mm') for the given column.
     *
     * @param int $col the column index (A = 1)
     */
    public function setFormatDateTime(int $col): self
    {
        return $this->setFormat($col, 'dd/mm/yyyy hh:mm');
    }

    /**
     * Sets the identifier format ('000000') for the given column.
     *
     * @param int $col the column index (A = 1)
     */
    public function setFormatId(int $col): self
    {
        return $this->setFormat($col, '000000');
    }

    /**
     * Sets the integer format ('#,##0') for the given column.
     *
     * @param int $col the column index (A = 1)
     */
    public function setFormatInt(int $col): self
    {
        return $this->setFormat($col, '#,##0');
    }

    /**
     * Sets the percent format for the given column.
     *
     * @param int  $col      the column index (A = 1)
     * @param bool $decimals true to display 2 decimals ('0.00%'), false if none ('0%').
     */
    public function setFormatPercent(int $col, bool $decimals = false): self
    {
        $format = $this->getPercentFormat($decimals);

        return $this->setFormat($col, $format);
    }

    /**
     * Sets the translated 'Yes/No' boolean format for the given column.
     *
     * @param int $col the column index (A = 1)
     */
    public function setFormatYesNo(int $col): self
    {
        return $this->setFormatBoolean($col, 'common.value_true', 'common.value_false', true);
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
     * @param array $headers the headers where key is the text to translate and value is the
     *                       horizontal alignment or if an array, the horizontal and the vertical
     *                       alignments and
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
     * @param int $col the column index (A = 1)
     * @param int $row the row index (1 = first row)
     */
    public function setSelectedCellByColumnAndRow(int $col, int $row): self
    {
        $coordinates = $this->stringFromColumnAndRowIndex($col, $row);

        return $this->setSelectedCell($coordinates);
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

        return  $this;
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
     * Get the string coordinate from the given column and row index (eg. 2,10 => 'B10').
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
