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

namespace App\Report;

use App\Controller\AbstractController;
use App\Entity\Calculation;
use App\Model\ImageData;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Pdf\Traits\PdfMemoryImageTrait;
use App\Report\Table\GroupsTable;
use App\Report\Table\ItemsTable;
use App\Report\Table\OverallTable;
use App\Traits\LoggerTrait;
use App\Utils\StringUtils;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use fpdf\Enums\PdfMove;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfBorder;
use Psr\Log\LoggerInterface;

/**
 * Report for a calculation.
 */
class CalculationReport extends AbstractReport
{
    use LoggerTrait;
    use PdfMemoryImageTrait;

    private const QR_CODE_SIZE = 38.0;

    public function __construct(
        AbstractController $controller,
        private readonly Calculation $calculation,
        private readonly float $minMargin,
        private readonly string $qrcode,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($controller);
    }

    /**
     * Gets the calculation.
     */
    public function getCalculation(): Calculation
    {
        return $this->calculation;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Gets the minimum allowed margin.
     */
    public function getMinMargin(): float
    {
        return $this->minMargin;
    }

    public function header(): void
    {
        parent::header();
        $this->renderCalculation();
    }

    public function render(): bool
    {
        $calculation = $this->calculation;
        $this->renderTitle($calculation);
        $this->addPage();
        if ($calculation->isEmpty()) {
            return $this->renderEmpty($calculation);
        }

        ItemsTable::render($this);
        $this->checkEndHeight($calculation);
        GroupsTable::render($this);
        OverallTable::render($this);
        $this->renderTimestampable($calculation);
        $this->renderQrCode();

        return true;
    }

    /**
     * Controls whether the rest of the calculation can be contained on the same page.
     */
    private function checkEndHeight(Calculation $calculation): void
    {
        // header + groups + footer
        $lines = $calculation->getGroupsCount() + 2;
        // net total + user margin
        if (!$this->isFloatZero($calculation->getUserMargin())) {
            $lines += 2;
        }
        // global margin + overall total + timestampable
        $lines += 3;
        $total = self::LINE_HEIGHT * (float) $lines;
        // QR code
        if ($this->isQrCode()) {
            $total += self::QR_CODE_SIZE;
        }
        if (!$this->isPrintable($total)) {
            $this->addPage();
        }
    }

    /**
     * Gets the QR code image data.
     *
     * @throws \Exception
     */
    private function getQrCodeImageData(): ImageData
    {
        $builder = new Builder(
            writer: new PngWriter(),
            data: $this->qrcode,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: (int) self::QR_CODE_SIZE,
            margin: 0,
        );
        $result = $builder->build();

        return ImageData::instance(
            data: $result->getString(),
            mimeType: $result->getMimeType()
        );
    }

    /**
     * Return if the QR code must render.
     */
    private function isQrCode(): bool
    {
        return StringUtils::isString($this->qrcode);
    }

    /**
     * Render the calculation properties (header).
     */
    private function renderCalculation(): void
    {
        $leftStyle = PdfStyle::getHeaderStyle()
            ->setBorder(PdfBorder::all()->setRight(false));
        $rightStyle = PdfStyle::getHeaderStyle()
            ->setBorder(PdfBorder::all()->setLeft(false));

        $calculation = $this->calculation;
        PdfTable::instance($this)
            ->addColumns(
                $this->leftColumn('', 100),
                $this->rightColumn('', 40, true)
            )
            ->startHeaderRow()
            ->add($calculation->getCustomer(), style: $leftStyle)
            ->add($calculation->getStateCode(), style: $rightStyle)
            ->endRow()
            ->startHeaderRow()
            ->add($calculation->getDescription(), style: $leftStyle)
            ->add($calculation->getFormattedDate(), style: $rightStyle)
            ->endRow();
        $this->lineBreak(3);
    }

    /**
     * Render a text when the calculation is empty.
     */
    private function renderEmpty(Calculation $calculation): true
    {
        PdfStyle::getHeaderStyle()
            ->setTextColor(PdfTextColor::red())
            ->apply($this);
        $this->cell(
            height: self::LINE_HEIGHT * 1.25,
            text: $this->trans('calculation.edit.empty'),
            border: PdfBorder::all(),
            move: PdfMove::BELOW,
            align: PdfTextAlignment::CENTER,
            fill: true
        );
        $this->renderTimestampable($calculation);

        return true;
    }

    /**
     * Render the QR code (if any).
     */
    private function renderQrCode(): void
    {
        if (!$this->isQrCode()) {
            return;
        }

        try {
            $data = $this->getQrCodeImageData();
            $x = $this->getPageWidth() - $this->getRightMargin() - self::QR_CODE_SIZE;
            $y = $this->getPageHeight() - ReportFooter::FOOTER_OFFSET - self::QR_CODE_SIZE;
            $this->imageData($data, $x, $y, self::QR_CODE_SIZE, self::QR_CODE_SIZE, $this->qrcode);
        } catch (\Exception $e) {
            $this->logException($e, $this->trans('report.calculation.error_qr_code'));
        }
    }

    private function renderTimestampable(Calculation $calculation): void
    {
        PdfStyle::getNoBorderStyle()
            ->setFontSize(6)
            ->apply($this);
        $this->useCellMargin(function () use ($calculation): void {
            $translator = $this->getTranslator();
            $width = $this->getPrintableWidth() / 2.0;
            $created = $calculation->getCreatedText($translator);
            $updated = $calculation->getUpdatedText($translator);
            $this->cell(width: $width, text: $created);
            $this->cell(width: $width, text: $updated, align: PdfTextAlignment::RIGHT);
        });
        $this->resetStyle();
    }

    /**
     * Set the title.
     */
    private function renderTitle(Calculation $calculation): void
    {
        $id = $calculation->getFormattedId();
        $this->setTitleTrans('calculation.edit.title', ['%id%' => $id], true);
    }
}
