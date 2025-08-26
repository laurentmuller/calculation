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

/**
 * Report outputting HTML content.
 */
class HtmlReport extends AbstractReport
{
    private readonly float $defaultLeftMargin;
    private readonly float $defaultRightMargin;

    public function __construct(
        AbstractController $controller,
        private readonly string $html,
        PdfOrientation $orientation = PdfOrientation::PORTRAIT
    ) {
        parent::__construct($controller, $orientation);
        $this->defaultLeftMargin = $this->getLeftMargin();
        $this->defaultRightMargin = $this->getRightMargin();
    }

    #[\Override]
    public function footer(): void
    {
        $previousMargins = $this->applyDefaultMargins();
        parent::footer();
        $this->applyPreviousMargins($previousMargins);
    }

    #[\Override]
    public function header(): void
    {
        $previousMargins = $this->applyDefaultMargins();
        parent::header();
        $this->applyPreviousMargins($previousMargins);
    }

    #[\Override]
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

    /**
     * @return array{0: float, 1: float}
     */
    private function applyDefaultMargins(): array
    {
        $leftMargin = $this->getLeftMargin();
        $rightMargin = $this->getRightMargin();
        if ($this->defaultLeftMargin !== $leftMargin) {
            $this->updateLeftMargin($this->defaultLeftMargin);
        }
        if ($this->defaultRightMargin !== $rightMargin) {
            $this->updateRightMargin($this->defaultRightMargin);
        }

        return [$leftMargin, $rightMargin];
    }

    /**
     * @param array{0: float, 1: float} $previousMargins
     */
    private function applyPreviousMargins(array $previousMargins): void
    {
        if ($previousMargins[0] !== $this->getLeftMargin()) {
            $this->updateLeftMargin($previousMargins[0]);
        }
        if ($previousMargins[1] !== $this->getRightMargin()) {
            $this->updateRightMargin($previousMargins[1]);
        }
    }

    private function parseContent(): ?HtmlParentChunk
    {
        $parser = new HtmlParser($this->html);

        return $parser->parse();
    }
}
