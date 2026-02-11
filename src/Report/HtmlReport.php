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
use fpdf\PdfMargins;

/**
 * Report outputting HTML content.
 */
class HtmlReport extends AbstractReport
{
    private readonly PdfMargins $defaultMargins;

    public function __construct(AbstractController $controller, private readonly string $html)
    {
        parent::__construct($controller);
        $this->defaultMargins = $this->getMargins();
    }

    #[\Override]
    public function footer(): void
    {
        $margins = $this->applyDefaultMargins();
        parent::footer();
        $this->applyPreviousMargins($margins);
    }

    #[\Override]
    public function header(): void
    {
        $margins = $this->applyDefaultMargins();
        parent::header();
        $this->applyPreviousMargins($margins);
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

    private function applyDefaultMargins(): PdfMargins
    {
        $margins = $this->getMargins();
        $this->updateMargins($margins, $this->defaultMargins);

        return $margins;
    }

    private function applyPreviousMargins(PdfMargins $previousMargins): void
    {
        $this->updateMargins($this->getMargins(), $previousMargins);
    }

    private function parseContent(): ?HtmlParentChunk
    {
        $parser = new HtmlParser($this->html);

        return $parser->parse();
    }

    private function updateMargins(PdfMargins $oldMargins, PdfMargins $newMargins): void
    {
        if ($newMargins->left !== $oldMargins->left) {
            $this->updateLeftMargin($newMargins->left);
        }
        if ($newMargins->right !== $oldMargins->right) {
            $this->updateRightMargin($newMargins->right);
        }
    }
}
