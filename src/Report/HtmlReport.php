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
use App\Pdf\Html\AbstractHtmlChunk;
use App\Pdf\Html\HtmlParentChunk;
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
     * The debug mode.
     */
    private bool $debug;

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
     * @param AbstractController $controller  the parent controller
     * @param string             $orientation the page orientation. One of the ORIENTATION_XX contants.
     * @param string             $unit        the measure unit. One of the UNIT_XX contants.
     * @param mixed              $size        the document size. One of the SIZE_XX contants or an array containing the width and height of the document.
     */
    public function __construct(AbstractController $controller, string $orientation = self::ORIENTATION_PORTRAIT, string $unit = self::UNIT_MILLIMETER, $size = self::SIZE_A4)
    {
        parent::__construct($controller, $orientation, $unit, $size);
        $this->debug = false;
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
     * Gets if the debug mode is enabled.
     *
     * @return bool true if enabled
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        // parse
        $parser = new HtmlParser($this->content);
        if (($root = $parser->parse()) !== null) {
            if ($this->debug) {
                $this->AddPage();
                $this->outputDebug($root);
            }

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
     * Sets if the debug mode is enabled.
     *
     * @param bool $debug true if enabled
     */
    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;

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

    private function outputDebug(AbstractHtmlChunk $chunk, int $indent = 0): void
    {
        // current
        $this->SetX($this->x + $indent);
        $this->Cell(0, self::LINE_HEIGHT, $chunk->__toString(), 0, 1);

        // children
        if ($chunk instanceof HtmlParentChunk) {
            foreach ($chunk->getChildren() as $child) {
                $this->outputDebug($child, $indent + 4);
            }
        }
    }
}
