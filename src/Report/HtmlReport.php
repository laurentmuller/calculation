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

use App\Pdf\Html\HtmlParser;

/**
 * Report to output HTML content.
 */
class HtmlReport extends AbstractReport
{
    private ?string $content = null;
    private ?float $leftMargin = null;
    private ?float $rightMargin = null;

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

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        if (null === $this->content || '' === $this->content) {
            return false;
        }
        $parser = new HtmlParser($this->content);
        if (null === $root = $parser->parse()) {
            return false;
        }
        $this->AddPage();
        $root->output($this);

        return true;
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
