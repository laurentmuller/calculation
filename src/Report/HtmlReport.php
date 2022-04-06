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

namespace App\Report;

use App\Controller\AbstractController;
use App\Pdf\Enums\PdfDocumentOrientation;
use App\Pdf\Enums\PdfDocumentSize;
use App\Pdf\Enums\PdfDocumentUnit;
use App\Pdf\Html\HtmlParser;

/**
 * Report to output HTML content.
 *
 * @author Laurent Muller
 */
class HtmlReport extends AbstractReport
{
    /**
     * the HTML content.
     */
    private ?string $content = null;

    /**
     * The left margin.
     */
    private ?float $leftMargin = null;

    /**
     * The right margin.
     */
    private ?float $rightMargin = null;

    /**
     * Constructor.
     *
     * @param PdfDocumentOrientation|string $orientation the page orientation
     * @param PdfDocumentUnit|string        $unit        the measure unit
     * @param PdfDocumentSize|int[]         $size        the document size or the width and height of the document
     */
    public function __construct(AbstractController $controller, PdfDocumentOrientation|string $orientation = PdfDocumentOrientation::PORTRAIT, PdfDocumentUnit|string $unit = PdfDocumentUnit::MILLIMETER, PdfDocumentSize|array $size = PdfDocumentSize::A4)
    {
        parent::__construct($controller, $orientation, $unit, $size);
    }

    /**
     * {@inheritdoc}
     */
    public function Footer(): void
    {
        $previousMargins = $this->applyDefaultMargins();
        parent::Footer();
        $this->applyPreviousMargins($previousMargins);
    }

    /**
     * {@inheritdoc}
     */
    public function Header(): void
    {
        $previousMargins = $this->applyDefaultMargins();
        parent::Header();
        $this->applyPreviousMargins($previousMargins);
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        // parse
        $parser = new HtmlParser($this->content);
        if (null !== ($root = $parser->parse())) {
            $this->AddPage();
            $root->output($this);

            return true;
        }

        return false;
    }

    /**
     * Sets the HTML content.
     */
    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
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
     * {@inheritdoc}
     *
     * @param string $orientation
     * @param mixed  $size
     * @param int    $rotation
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
        if ($leftMargin !== $this->leftMargin) {
            $this->lMargin = $this->x = $this->leftMargin;
        }
        if ($rightMargin !== $this->rightMargin) {
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
}
