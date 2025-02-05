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
use fpdf\Enums\PdfOrientation;
use fpdf\Enums\PdfPageSize;
use fpdf\Enums\PdfRotation;
use fpdf\PdfSize;

/**
 * Report outputting HTML content.
 */
class HtmlReport extends AbstractReport
{
    private ?float $defaultLeftMargin = null;
    private ?float $defaultRightMargin = null;

    public function __construct(
        AbstractController $controller,
        private readonly string $html,
        PdfOrientation $orientation = PdfOrientation::PORTRAIT
    ) {
        parent::__construct($controller, $orientation);
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
        $this->setLeftMargin($leftMargin);
        $this->x = $leftMargin;

        return $this;
    }

    /**
     * Update the right margin.
     *
     * @param float $rightMargin the margin to set
     */
    public function updateRightMargin(float $rightMargin): self
    {
        $this->setRightMargin($rightMargin);

        return $this;
    }

    protected function beginPage(
        ?PdfOrientation $orientation = null,
        PdfPageSize|PdfSize|null $size = null,
        ?PdfRotation $rotation = null
    ): void {
        parent::beginPage($orientation, $size, $rotation);
        if (1 === $this->page) {
            $this->defaultLeftMargin = $this->getLeftMargin();
            $this->defaultRightMargin = $this->getRightMargin();
        }
    }

    /**
     * Apply the default left and right margins.
     *
     * @return array{0: float, 1: float} the previous left and right margins
     */
    private function applyDefaultMargins(): array
    {
        $leftMargin = $this->getLeftMargin();
        $rightMargin = $this->getRightMargin();
        if (null !== $this->defaultLeftMargin && $this->defaultLeftMargin !== $leftMargin) {
            $this->x = $this->defaultLeftMargin;
        }
        if (null !== $this->defaultRightMargin && $this->defaultRightMargin !== $rightMargin) {
            $this->setRightMargin($this->defaultRightMargin);
        }

        return [$leftMargin, $rightMargin];
    }

    /**
     * Apply the previous left and right margins.
     *
     * @param array{0: float, 1: float} $previousMargins the previous left and right margins to apply
     */
    private function applyPreviousMargins(array $previousMargins): void
    {
        if ($previousMargins[0] !== $this->getLeftMargin()) {
            $this->setLeftMargin($previousMargins[0]);
            $this->x = $previousMargins[0];
        }
        if ($previousMargins[1] !== $this->getRightMargin()) {
            $this->setRightMargin($previousMargins[1]);
        }
    }

    private function parseContent(): ?HtmlParentChunk
    {
        $parser = new HtmlParser($this->html);

        return $parser->parse();
    }
}
