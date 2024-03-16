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
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Pdf\Traits\PdfMemoryImageTrait;
use App\Report\Table\TableGroups;
use App\Report\Table\TableItems;
use App\Report\Table\TableOverall;
use App\Traits\LoggerTrait;
use App\Utils\StringUtils;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\GdResult;
use fpdf\PdfBorder;
use fpdf\PdfMove;
use fpdf\PdfTextAlignment;
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
            $this->renderEmpty();

            return true;
        }

        TableItems::render($this);
        $this->checkEndHeight($calculation);
        TableGroups::render($this);
        TableOverall::render($this);
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
        $border = PdfBorder::all()->setRight(false);
        $leftStyle = PdfStyle::getHeaderStyle()
            ->setBorder($border);
        $border = PdfBorder::all()->setLeft(false);
        $rightStyle = PdfStyle::getHeaderStyle()
            ->setBorder($border);

        $calculation = $this->calculation;
        PdfTable::instance($this)
            ->addColumns(
                PdfColumn::left(null, 100),
                PdfColumn::right(null, 40, true)
            )
            ->startHeaderRow()
            ->add(text: $calculation->getCustomer(), style: $leftStyle)
            ->add(text: $calculation->getStateCode(), style: $rightStyle)
            ->endRow()
            ->startHeaderRow()
            ->add(text: $calculation->getDescription(), style: $leftStyle)
            ->add(text: $calculation->getFormattedDate(), style: $rightStyle)
            ->endRow();
        $this->lineBreak(3);
    }

    /**
     * Render a text when the calculation is empty.
     */
    private function renderEmpty(): void
    {
        PdfStyle::getHeaderStyle()->apply($this);
        $message = $this->trans('calculation.edit.empty');
        $this->cell(
            height: self::LINE_HEIGHT * 1.8,
            text: $message,
            border: PdfBorder::all(),
            move: PdfMove::NEW_LINE,
            align: PdfTextAlignment::CENTER,
            fill: true
        );
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
            /** @psalm-var GdResult $result */
            $result = Builder::create()
                ->errorCorrectionLevel(ErrorCorrectionLevel::Medium)
                ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
                ->size((int) self::QR_CODE_SIZE)
                ->writer(new PngWriter())
                ->data($this->qrcode)
                ->margin(0)
                ->build();

            $image = $result->getImage();
            $x = $this->getPageWidth() - $this->getRightMargin() - self::QR_CODE_SIZE;
            $y = $this->getPageHeight() - self::FOOTER_OFFSET - self::QR_CODE_SIZE;
            $this->imageGD($image, $x, $y, self::QR_CODE_SIZE, self::QR_CODE_SIZE, $this->qrcode);
        } catch (\Exception $e) {
            $this->logException($e, $this->trans('report.calculation.error_qr_code'));
        }
    }

    private function renderTimestampable(Calculation $calculation): void
    {
        PdfStyle::getNoBorderStyle()
            ->setFontSize(6)
            ->apply($this);
        $width = $this->getPrintableWidth() / 2.0;
        $this->useCellMargin(function () use ($calculation, $width): void {
            $translator = $this->getTranslator();
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
