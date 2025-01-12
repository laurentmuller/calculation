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
use App\Traits\TranslatorTrait;
use App\Utils\StringUtils;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extends the Spreadsheet class with shortcuts to set properties.
 */
class SpreadsheetDocument extends Spreadsheet
{
    use TranslatorTrait;

    /**
     * The file title.
     */
    private ?string $title = null;

    /**
     * @throws Exception
     */
    public function __construct(private readonly TranslatorInterface $translator)
    {
        parent::__construct();

        // replace default sheet
        $this->removeSheetByIndex(0);
        $this->createSheet();
    }

    /**
     * Add external sheet.
     *
     * @param Worksheet $worksheet  the external sheet to add
     * @param ?int      $sheetIndex the index where the sheet should go (0, 1, ... or null for last)
     *
     * @throws Exception if the given worksheet is not an instance of WorksheetDocument
     */
    public function addExternalSheet(Worksheet $worksheet, ?int $sheetIndex = null): WorksheetDocument
    {
        if (!$worksheet instanceof WorksheetDocument) {
            throw new Exception(\sprintf('%s expected, %s given.', WorksheetDocument::class, \get_debug_type($worksheet)));
        }

        parent::addExternalSheet($worksheet, $sheetIndex);

        return $worksheet;
    }

    /**
     * Add a sheet.
     *
     * @param Worksheet $worksheet  the worksheet to add
     * @param ?int      $sheetIndex the index where the sheet should go (0, 1, ... or null for last)
     *
     * @throws Exception if the given worksheet is not an instance of WorksheetDocument
     */
    public function addSheet(
        Worksheet $worksheet,
        ?int $sheetIndex = null,
        bool $retitleIfNeeded = false
    ): WorksheetDocument {
        $worksheet = $this->validateSheet($worksheet);
        parent::addSheet($worksheet, $sheetIndex, $retitleIfNeeded);

        return $worksheet;
    }

    /**
     * Create a sheet and add it to this workbook.
     *
     * @param ?int $sheetIndex the index where the sheet should go (0, 1, ..., or null for last)
     *
     * @throws Exception
     */
    public function createSheet(?int $sheetIndex = null): WorksheetDocument
    {
        return $this->addSheet(new WorksheetDocument($this), $sheetIndex, true);
    }

    /**
     * Create a worksheet, set the title, and add it to this spreadsheet.
     *
     * The created sheet is activated.
     *
     * @param ?string $title      the title of the worksheet
     * @param ?int    $sheetIndex the index where the worksheet should go (0, 1, ..., or null for last)
     *
     * @return WorksheetDocument the newly created worksheet
     *
     * @throws Exception
     */
    public function createSheetAndTitle(
        AbstractController $controller,
        ?string $title = null,
        ?int $sheetIndex = null
    ): WorksheetDocument {
        $sheet = $this->createSheet($sheetIndex);
        if (null !== $title) {
            $sheet->setTitle($title);
        }

        $this->setActiveSheetIndex($sheetIndex ?? $this->getSheetCount() - 1);
        $customer = $controller->getUserService()->getCustomer();

        return $sheet->setPrintGridlines(true)
            ->updateHeaderFooter($customer);
    }

    /**
     * Get the active sheet.
     */
    public function getActiveSheet(): WorksheetDocument
    {
        return $this->validateSheet(parent::getActiveSheet());
    }

    /**
     * Get all sheets.
     *
     * @return WorksheetDocument[]
     */
    public function getAllSheets(): array
    {
        /** @psalm-var WorksheetDocument[] */
        return parent::getAllSheets();
    }

    /**
     * Get a sheet by the given index.
     *
     * @param int $sheetIndex Sheet index
     *
     * @throws Exception
     */
    public function getSheet(int $sheetIndex): WorksheetDocument
    {
        return $this->validateSheet(parent::getSheet($sheetIndex));
    }

    /**
     * Get a sheet by name.
     *
     * @param string $worksheetName Sheet name
     */
    public function getSheetByName(string $worksheetName): ?WorksheetDocument
    {
        $sheet = parent::getSheetByName($worksheetName);
        if (!$sheet instanceof Worksheet) {
            return null;
        }

        return $this->validateSheet($sheet);
    }

    /**
     * Get a sheet by name, throwing exception if not found.
     *
     * @throws Exception
     */
    public function getSheetByNameOrThrow(string $worksheetName): WorksheetDocument
    {
        return $this->validateSheet(parent::getSheetByNameOrThrow($worksheetName));
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
     * Set active sheet index.
     *
     * @param int $worksheetIndex Active sheet index
     *
     * @throws Exception
     */
    public function setActiveSheetIndex(int $worksheetIndex): WorksheetDocument
    {
        return $this->validateSheet(parent::setActiveSheetIndex($worksheetIndex));
    }

    /**
     * Set the active sheet index by name.
     *
     * @param string $worksheetName Sheet title
     *
     * @throws Exception
     */
    public function setActiveSheetIndexByName(string $worksheetName): WorksheetDocument
    {
        return $this->validateSheet(parent::setActiveSheetIndexByName($worksheetName));
    }

    /**
     * Sets the title of the active sheet.
     *
     * If this parent's controller is not null, the header and footer are also updated.
     */
    public function setActiveTitle(string $title, ?AbstractController $controller = null): static
    {
        $sheet = $this->getActiveSheet()
            ->setTitle($title);
        if ($controller instanceof AbstractController) {
            $customer = $controller->getUserService()->getCustomer();
            $sheet->updateHeaderFooter($customer);
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
        if (StringUtils::isString($category)) {
            $this->getProperties()->setCategory($category);
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
        if (StringUtils::isString($company)) {
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
        if (StringUtils::isString($description)) {
            $this->getProperties()->setDescription($description);
        }

        return $this;
    }

    /**
     * Sets the document description to be translated.
     *
     * @param string|\Stringable|TranslatableInterface $id         the description identifier
     *                                                             (may also be an object that can be cast to string)
     * @param array                                    $parameters an array of parameters for the message
     */
    public function setDescriptionTrans(string|\Stringable|TranslatableInterface $id, array $parameters = []): static
    {
        return $this->setDescription($this->trans($id, $parameters));
    }

    /**
     * Sets the subject property.
     *
     * @param ?string $subject the subject
     */
    public function setSubject(?string $subject): static
    {
        if (StringUtils::isString($subject)) {
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
        if (StringUtils::isString($title)) {
            $this->getProperties()->setTitle($title);
        }

        return $this;
    }

    /**
     * Sets the title to be translated.
     *
     * @param string|\Stringable|TranslatableInterface $id         the title identifier
     *                                                             (may also be an object that can be cast to string)
     * @param array                                    $parameters an array of parameters for the message
     */
    public function setTitleTrans(string|\Stringable|TranslatableInterface $id, array $parameters = []): static
    {
        return $this->setTitle($this->trans($id, $parameters));
    }

    /**
     * Sets the username for the creator and the last modified properties.
     */
    public function setUserName(?string $userName = null): static
    {
        if (StringUtils::isString($userName)) {
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
        $userName = $controller->getUserIdentifier();
        $title = $this->trans($title);

        $sheet = $this->getActiveSheet()
            ->setPrintGridlines(true)
            ->setTitle($title)
            ->updateHeaderFooter($customer);
        if ($landscape) {
            $sheet->setPageLandscape();
        }

        return $this->setTitle($title)
            ->setCompany($customer->getName())
            ->setUserName($userName)
            ->setCategory($application);
    }

    protected function validateSheet(Worksheet $sheet): WorksheetDocument
    {
        if (!$sheet instanceof WorksheetDocument) {
            throw new Exception(\sprintf('%s expected, %s given.', WorksheetDocument::class, \get_debug_type($sheet)));
        }

        return $sheet;
    }
}
