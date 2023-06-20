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
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extends the Spreadsheet class with shortcuts to set properties.
 */
class SpreadsheetDocument extends Spreadsheet
{
    use TranslatorTrait;

    /**
     * The top margins when customer header is present (21 millimeters).
     */
    final public const HEADER_CUSTOMER_MARGIN = 0.83;

    /**
     * The top and bottom margins when header and/or footer is present (12 millimeters).
     */
    final public const HEADER_FOOTER_MARGIN = 0.47;

    /**
     * The file title.
     */
    private ?string $title = null;

    /**
     * Constructor.
     */
    public function __construct(private readonly TranslatorInterface $translator)
    {
        parent::__construct();

        // replace default sheet
        $this->removeSheetByIndex(0);
        $this->createSheet()
            ->setPageSizeA4()
            ->setPagePortrait();
    }

    /**
     * Create a sheet and add it to this workbook.
     *
     * @param int|null $sheetIndex Index where sheet should go (0,1,..., or null for last)
     */
    public function createSheet($sheetIndex = null): WorksheetDocument
    {
        $newSheet = new WorksheetDocument($this);
        $this->addSheet($newSheet, $sheetIndex);

        return $newSheet;
    }

    /**
     * Create a worksheet, set title and add it to this spreadsheet.
     *
     * The created sheet is activated.
     *
     * @param ?string $title      the title of the worksheet
     * @param ?int    $sheetIndex the  index where worksheet should go (0,1,..., or null for last)
     *
     * @return WorksheetDocument the newly created worksheet
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function createSheetAndTitle(AbstractController $controller, string $title = null, int $sheetIndex = null): WorksheetDocument
    {
        $sheet = $this->createSheet($sheetIndex);
        if (null !== $title) {
            $sheet->setTitle($this->validateSheetTitle($title));
        }
        $sheet->setPrintGridlines(true);

        $this->setActiveSheetIndex($sheetIndex ?? $this->getSheetCount() - 1);
        $customer = $controller->getUserService()->getCustomer();
        $this->setHeaderFooter($title, $customer);

        return $sheet;
    }

    /**
     * Get active sheet.
     */
    public function getActiveSheet(): WorksheetDocument
    {
        /** @psalm-var WorksheetDocument $sheet */
        $sheet = parent::getActiveSheet();

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
     * Gets the title.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Set merge on a cell range by using cell coordinates.
     *
     * @param int  $startColumn the index of the first column ('A' = First column)
     * @param int  $endColumn   the index of the last column
     * @param int  $startRow    the index of first row (1 = First row)
     * @param ?int $endRow      the index of the last cell or null to use the start row
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception if an exception occurs
     */
    public function mergeCells(int $startColumn, int $endColumn, int $startRow, int $endRow = null): static
    {
        $this->getActiveSheet()->mergeCells([$startColumn, $startRow, $endColumn, $endRow ?? $startRow]);

        return $this;
    }

    /**
     * Sets the title of the active sheet.
     *
     * If this parent's controller is not null, the header and footer are also updated.
     */
    public function setActiveTitle(string $title, AbstractController $controller = null): static
    {
        $title = $this->validateSheetTitle($title);
        $this->getActiveSheet()->setTitle($title);
        if ($controller instanceof AbstractController) {
            $customer = $controller->getUserService()->getCustomer();
            $this->setHeaderFooter($title, $customer);
        }

        return $this;
    }

    /**
     * Sets the category property.
     *
     * @param ?string $category the category
     */
    public function setCategory(?string $category): static
    {
        if ($category) {
            $this->getProperties()->setCategory($category);
        }

        return $this;
    }

    /**
     * Set a cell value by using numeric cell coordinates.
     *
     * Do nothing if the value is null or empty('').
     *
     * @param Worksheet $sheet       the work sheet to write value to
     * @param int       $columnIndex the column index ('A' = First column)
     * @param int       $rowIndex    the row index (1 = First row)
     * @param mixed     $value       the value to set
     */
    public function setCellValue(Worksheet $sheet, int $columnIndex, int $rowIndex, mixed $value): static
    {
        if (null !== $value && '' !== $value) {
            if ($value instanceof \DateTimeInterface) {
                $value = Date::PHPToExcel($value);
            } elseif (\is_bool($value)) {
                $value = (int) $value;
            }
            $sheet->setCellValue([$columnIndex, $rowIndex], $value);
        }

        return $this;
    }

    /**
     * Sets the company name property.
     *
     * @param ?string $company the company name
     */
    public function setCompany(?string $company): static
    {
        if ($company) {
            $this->getProperties()->setCompany($company);
        }

        return $this;
    }

    /**
     * Sets the document description.
     *
     * @param ?string $description the description
     */
    public function setDescription(?string $description): static
    {
        if ($description) {
            $this->getProperties()->setDescription($description);
        }

        return $this;
    }

    /**
     * Sets the values of the given row.
     *
     * @param int   $rowIndex    the row index (1 = First row)
     * @param array $values      the values to set
     * @param int   $columnIndex the starting column index ('A' = First column)
     */
    public function setRowValues(int $rowIndex, array $values, int $columnIndex = 1): static
    {
        $sheet = $this->getActiveSheet();
        /** @psalm-var mixed $value*/
        foreach ($values as $value) {
            $this->setCellValue($sheet, $columnIndex++, $rowIndex, $value);
        }

        return $this;
    }

    /**
     * Sets the subject property.
     *
     * @param ?string $subject the subject
     */
    public function setSubject(?string $subject): static
    {
        if ($subject) {
            $this->getProperties()->setSubject($subject);
        }

        return $this;
    }

    /**
     * Sets the document title.
     */
    public function setTitle(?string $title): static
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
    public function setUserName(?string $userName): static
    {
        if ($userName) {
            $this->getProperties()
                ->setCreator($userName)
                ->setLastModifiedBy($userName);
        }

        return $this;
    }

    /**
     * Initialize this spreadsheet.
     *
     * @param AbstractController $controller the controller to get properties
     * @param string             $title      the spreadsheet title to translate
     * @param bool               $landscape  true to set landscape orientation, false for default (portrait)
     */
    protected function initialize(AbstractController $controller, string $title, bool $landscape = false): static
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
            ->setCategory($application);

        $sheet = $this->getActiveSheet()
            ->setPrintGridlines(true);
        if ($landscape) {
            $sheet->setPageLandscape();
        }

        return $this;
    }

    /**
     * Sets the header and footer texts.
     */
    private function setHeaderFooter(?string $title, CustomerInformation $customer): static
    {
        $sheet = $this->getActiveSheet();
        $pageMargins = $sheet->getPageMargins();
        $header = HeaderFooter::header();
        if ($customer->isPrintAddress()) {
            $header->addLeft($customer->getName(), true)
                ->addLeft($customer->getAddress())
                ->addLeft($customer->getZipCity())
                ->addCenter($title, true)
                ->addRight($customer->getTranslatedPhone($this))
                ->addRight($customer->getTranslatedFax($this))
                ->addRight($customer->getEmail());
            $pageMargins->setTop(self::HEADER_CUSTOMER_MARGIN);
        } else {
            $header->addLeft($title, true)
                ->addRight($customer->getName(), true);
            $pageMargins->setTop(self::HEADER_FOOTER_MARGIN);
        }
        $header->apply($sheet);
        $pageMargins->setBottom(self::HEADER_FOOTER_MARGIN);
        HeaderFooter::footer()
            ->addPages()
            ->addDateTime()
            ->apply($sheet);

        return $this;
    }

    /**
     * Validate the worksheet title.
     */
    private function validateSheetTitle(string $title): string
    {
        /** @var string[] $invalidChars */
        $invalidChars = Worksheet::getInvalidCharacters();
        $title = \str_replace($invalidChars, '', $title);
        if (StringHelper::countCharacters($title) > Worksheet::SHEET_TITLE_MAXIMUM_LENGTH) {
            return StringHelper::substring($title, 0, Worksheet::SHEET_TITLE_MAXIMUM_LENGTH);
        }

        return $title;
    }
}
