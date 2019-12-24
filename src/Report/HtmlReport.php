<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Report;

use App\Controller\BaseController;
use App\Pdf\Html\HtmlChunk;
use App\Pdf\Html\HtmlParentChunk;
use App\Pdf\Html\HtmlParser;

/**
 * Report to output HTML content.
 *
 * @author Laurent Muller
 */
class HtmlReport extends BaseReport
{
    /**
     * the HTML content.
     *
     * @var string
     */
    private $content;

    /**
     * The debug mode.
     *
     * @var bool
     */
    private $debug;

    /**
     * The left margin.
     *
     * @var float
     */
    private $leftMargin;

    /**
     * The right margin.
     *
     * @var float
     */
    private $rightMargin;

    /**
     * Constructor.
     *
     * @param BaseController $controller  the parent controller
     * @param string         $orientation the page orientation. One of the ORIENTATION_XX contants.
     * @param string         $unit        the measure unit. One of the UNIT_XX contants.
     * @param mixed          $size        the document size. One of the SIZE_XX contants or an array containing the width and height of the document.
     */
    public function __construct(BaseController $controller, string $orientation = self::ORIENTATION_PORTRAIT, string $unit = self::UNIT_MILLIMETER, $size = self::SIZE_A4)
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
        if ($root = $parser->parse()) {
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

    private function outputDebug(HtmlChunk $chunk, int $indent = 0): void
    {
        // current
        $this->SetX($this->x + $indent);
        $this->Cell(0, self::LINE_HEIGHT, $chunk->__toString());
        $this->Ln();

        // children
        if ($chunk instanceof HtmlParentChunk) {
            foreach ($chunk->getChildren() as $child) {
                $this->outputDebug($child, $indent + 4);
            }
        }
    }
}
