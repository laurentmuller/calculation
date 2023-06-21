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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
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
        $this->createSheet();
    }

    /**
     * Create a sheet and add it to this workbook.
     *
     * @param int|null $sheetIndex Index where sheet should go (0,1,..., or null for last)
     */
    public function createSheet($sheetIndex = null): WorksheetDocument
    {
        $sheet = new WorksheetDocument($this);
        $this->addSheet($sheet, $sheetIndex);

        return $sheet;
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
            $sheet->setTitle($title);
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
     * Sets the title of the active sheet.
     *
     * If this parent's controller is not null, the header and footer are also updated.
     */
    public function setActiveTitle(string $title, AbstractController $controller = null): static
    {
        $sheet = $this->getActiveSheet()
            ->setTitle($title);
        if ($controller instanceof AbstractController) {
            return $this->setHeaderFooter($sheet->getTitle(), $controller->getUserService()->getCustomer());
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
        if ($customer->isPrintAddress()) {
            HeaderFooter::header()
                ->addLeft($customer->getName(), true)
                ->addLeft($customer->getAddress())
                ->addLeft($customer->getZipCity())
                ->addCenter($title, true)
                ->addRight($customer->getTranslatedPhone($this))
                ->addRight($customer->getTranslatedFax($this))
                ->addRight($customer->getEmail())
                ->apply($sheet);
            $pageMargins->setTop(self::HEADER_CUSTOMER_MARGIN);
        } else {
            HeaderFooter::header()
                ->addLeft($title, true)
                ->addRight($customer->getName(), true)
                ->apply($sheet);
            $pageMargins->setTop(self::HEADER_FOOTER_MARGIN);
        }

        HeaderFooter::footer()
            ->addPages()
            ->addDateTime()
            ->apply($sheet);
        $pageMargins->setBottom(self::HEADER_FOOTER_MARGIN);

        return $this;
    }
}
