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
use App\Pdf\Enums\PdfMove;
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfBorder;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Traits\LoggerTrait;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeNone;
use Endroid\QrCode\Writer\PdfWriter;
use Psr\Log\LoggerInterface;

/**
 * Report for a calculation.
 */
class CalculationReport extends AbstractReport
{
    use LoggerTrait;

    private readonly float $minMargin;

    /**
     * Constructor.
     */
    public function __construct(AbstractController $controller, private readonly LoggerInterface $logger, private readonly Calculation $calculation, private readonly ?string $qrcode = null)
    {
        parent::__construct($controller);
        $this->minMargin = $controller->getApplication()->getMinMargin();
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

    public function Header(): void
    {
        parent::Header();
        $this->renderCalculation();
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        $calculation = $this->calculation;
        if ($calculation->isNew()) {
            $this->setTitleTrans('calculation.add.title');
        } else {
            $id = $calculation->getFormattedId();
            $this->setTitleTrans('calculation.edit.title', ['%id%' => $id], true);
        }
        $this->AddPage();
        if ($calculation->isEmpty()) {
            $this->resetStyle()->Ln();
            $message = $this->trans('calculation.edit.empty');
            $this->Cell(txt: $message, ln: PdfMove::NEW_LINE, align: PdfTextAlignment::CENTER);
            $this->renderQrCode();

            return true;
        }
        CalculationTableItems::render($this);
        $this->Ln(3);
        $this->checkTablesHeight($calculation);
        CalculationTableGroups::render($this);
        CalculationTableOverall::render($this);
        $this->renderQrCode();

        return true;
    }

    /**
     * Checks if the groups table, the overall table and the QR code (if any) fit within the current page.
     */
    private function checkTablesHeight(Calculation $calculation): void
    {
        // groups, user magin and totalss
        $lines = $calculation->getGroupsCount() + 2;
        if (!empty($calculation->getUserMargin())) {
            $lines += 2;
        }
        $lines += 3;
        $total = 2.0 + self::LINE_HEIGHT * (float) $lines;
        // qr code
        if ($this->isQrCode()) {
            $total += $this->getQrCodeSize() - 1.0;
        }
        if (!$this->isPrintable($total)) {
            $this->AddPage();
        }
    }

    /**
     * Gets the QR code size (if any) in millimeters; 0 if none.
     */
    private function getQrCodeSize(): float
    {
        return $this->isQrCode() ? 30 : 0;
    }

    /**
     * Return if the QR code must render.
     */
    private function isQrCode(): bool
    {
        return null !== $this->qrcode &&  '' !== $this->qrcode;
    }

    /**
     * Render the calculation properties (header).
     */
    private function renderCalculation(): void
    {
        $calculation = $this->calculation;
        $leftStyle = PdfStyle::getHeaderStyle()
            ->setBorder(PdfBorder::TOP . PdfBorder::BOTTOM . PdfBorder::LEFT);
        $rightStyle = PdfStyle::getHeaderStyle()
            ->setBorder(PdfBorder::TOP . PdfBorder::BOTTOM . PdfBorder::RIGHT);
        PdfTableBuilder::instance($this)
            ->addColumns(
                PdfColumn::left(null, 100),
                PdfColumn::right(null, 40, true)
            )->startHeaderRow()
            ->add(text: $calculation->getCustomer(), style: $leftStyle)
            ->add(text: $calculation->getStateCode(), style: $rightStyle)
            ->endRow()
            ->startHeaderRow()
            ->add(text: $calculation->getDescription(), style: $leftStyle)
            ->add(text: $calculation->getFormattedDate(), style: $rightStyle)
            ->endRow();
        $this->Ln(3);
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
            $size = $this->getQrCodeSize();
            $options = [
                PdfWriter::WRITER_OPTION_Y => $this->GetPageHeight() + self::FOOTER_OFFSET - $size - 1.0,
                PdfWriter::WRITER_OPTION_X => $this->GetPageWidth() - $this->getRightMargin() - $size,
                PdfWriter::WRITER_OPTION_LINK => $this->qrcode,
                PdfWriter::WRITER_OPTION_PDF => $this,
            ];
            Builder::create()
                ->roundBlockSizeMode(new RoundBlockSizeModeNone())
                ->data((string) $this->qrcode)
                ->writer(new PdfWriter())
                ->writerOptions($options)
                ->size((int) $size)
                ->margin(0)
                ->build();
        } catch (\Exception $e) {
            $this->logException($e, $this->trans('report.calculation.error_qr_code'));
        }
    }
}
