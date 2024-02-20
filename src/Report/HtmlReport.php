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
use App\Pdf\Html\HtmlParentChunk;
use App\Pdf\Html\HtmlParser;
use fpdf\PdfOrientation;
use fpdf\PdfPageSize;
use fpdf\PdfRotation;
use fpdf\PdfSize;
use fpdf\PdfUnit;

/**
 * Report to output HTML content.
 */
class HtmlReport extends AbstractReport
{
    private ?float $currentLeftMargin = null;
    private ?float $currentRightMargin = null;

    public function __construct(
        AbstractController $controller,
        private readonly string $html,
        PdfOrientation $orientation = PdfOrientation::PORTRAIT,
        PdfUnit $unit = PdfUnit::MILLIMETER,
        PdfPageSize $size = PdfPageSize::A4
    ) {
        parent::__construct($controller, $orientation, $unit, $size);
    }

    public function footer(): void
    {
        $previousMargins = $this->applyDefaultMargins();
        parent::footer();
        $this->applyPreviousMargins($previousMargins);
    }

    public function header(): void
    {
        $previousMargins = $this->applyDefaultMargins();
        parent::header();
        $this->applyPreviousMargins($previousMargins);
    }

    public function render(): bool
    {
        if ('' === $this->html) {
            return false;
        }

        $root = $this->parseContent();
        if (!$root instanceof HtmlParentChunk) {
            return false;
        }

        $this->addPage();
        $root->output($this);

        return true;
    }

    /**
     * Update the left margin.
     *
     * @param float $leftMargin the margin to set
     */
    public function updateLeftMargin(float $leftMargin): self
    {
        $this->x = $this->leftMargin = $leftMargin;

        return $this;
    }

    /**
     * Update the right margin.
     *
     * @param float $rightMargin the margin to set
     */
    public function updateRightMargin(float $rightMargin): self
    {
        $this->rightMargin = $rightMargin;

        return $this;
    }

    protected function beginPage(
        ?PdfOrientation $orientation = null,
        PdfPageSize|PdfSize|null $size = null,
        ?PdfRotation $rotation = null
    ): void {
        parent::beginPage($orientation, $size, $rotation);
        if (1 === $this->page) {
            $this->currentLeftMargin = $this->leftMargin;
            $this->currentRightMargin = $this->rightMargin;
        }
    }

    /**
     * Apply the default left and right margins.
     *
     * @return float[] the previous left and right margins
     */
    private function applyDefaultMargins(): array
    {
        $leftMargin = $this->leftMargin;
        $rightMargin = $this->rightMargin;
        if (null !== $this->currentLeftMargin && $this->currentLeftMargin !== $leftMargin) {
            $this->x = $this->rightMargin = $this->currentLeftMargin;
        }
        if (null !== $this->currentRightMargin && $this->currentRightMargin !== $rightMargin) {
            $this->rightMargin = $this->currentRightMargin;
        }

        return [$leftMargin, $rightMargin];
    }

    /**
     * Apply the previous left and right margins.
     *
     * @param float[] $previousMargins the previous left and right margins to apply
     */
    private function applyPreviousMargins(array $previousMargins): void
    {
        if ($previousMargins[0] !== $this->leftMargin) {
            $this->leftMargin = $this->x = $previousMargins[0];
        }
        if ($previousMargins[1] !== $this->rightMargin) {
            $this->rightMargin = $previousMargins[1];
        }
    }

    private function parseContent(): ?HtmlParentChunk
    {
        $parser = new HtmlParser($this->html);

        return $parser->parse();
    }
}
