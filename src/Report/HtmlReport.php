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
use App\Pdf\Enums\PdfDocumentOrientation;
use App\Pdf\Enums\PdfDocumentSize;
use App\Pdf\Enums\PdfDocumentUnit;
use App\Pdf\Html\HtmlParentChunk;
use App\Pdf\Html\HtmlParser;

/**
 * Report to output HTML content.
 */
class HtmlReport extends AbstractReport
{
    private ?float $leftMargin = null;
    private ?float $rightMargin = null;

    public function __construct(
        AbstractController $controller,
        private readonly string $content,
        PdfDocumentOrientation $orientation = PdfDocumentOrientation::PORTRAIT,
        PdfDocumentUnit $unit = PdfDocumentUnit::MILLIMETER,
        PdfDocumentSize $size = PdfDocumentSize::A4
    ) {
        parent::__construct($controller, $orientation, $unit, $size);
    }

    public function Footer(): void
    {
        $previousMargins = $this->applyDefaultMargins();
        parent::Footer();
        $this->applyPreviousMargins($previousMargins);
    }

    public function Header(): void
    {
        $previousMargins = $this->applyDefaultMargins();
        parent::Header();
        $this->applyPreviousMargins($previousMargins);
    }

    public function render(): bool
    {
        if ('' === $this->content) {
            return false;
        }

        if (!$root = $this->parseContent()) {
            return false;
        }

        $this->AddPage();
        $root->output($this);

        return true;
    }

    /**
     * Update the left margin.
     *
     * @param float $margin the margin to set
     */
    public function updateLeftMargin(float $margin): self
    {
        $this->SetLeftMargin($margin);
        $this->SetX($margin);

        return $this;
    }

    /**
     * Update the right margin.
     *
     * @param float $margin the margin to set
     */
    public function updateRightMargin(float $margin): self
    {
        $this->SetRightMargin($margin);

        return $this;
    }

    /**
     * @param string         $orientation
     * @param string|float[] $size
     * @param string         $rotation
     */
    protected function _beginpage($orientation, $size, $rotation): void
    {
        parent::_beginpage($orientation, $size, $rotation);
        if (1 === $this->page) {
            $this->leftMargin = $this->lMargin;
            $this->rightMargin = $this->rMargin;
        }
    }

    /**
     * Apply the default left and right margins.
     *
     * @return float[] the previous left and right margins
     */
    private function applyDefaultMargins(): array
    {
        $leftMargin = $this->lMargin;
        $rightMargin = $this->rMargin;
        if (null !== $this->leftMargin && $this->leftMargin !== $leftMargin) {
            $this->lMargin = $this->x = $this->leftMargin;
        }
        if (null !== $this->rightMargin && $this->rightMargin !== $rightMargin) {
            $this->rMargin = $this->rightMargin;
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
            $this->lMargin = $this->x = $previousMargins[0];
        }
        if ($previousMargins[1] !== $this->rightMargin) {
            $this->rMargin = $previousMargins[1];
        }
    }

    private function parseContent(): HtmlParentChunk|false
    {
        $parser = new HtmlParser($this->content);

        return $parser->parse();
    }
}
