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

namespace App\Pdf\Html;

/**
 * Specialized chunk for HTML ordered list (ol).
 */
class HtmlOlChunk extends AbstractHtmlListChunk
{
    /**
     * The start counting.
     *
     * @psalm-var positive-int
     */
    private int $start = 1;

    /**
     * The numbered type.
     */
    private HtmlListType $type = HtmlListType::NUMBER;

    public function getBulletLast(): string
    {
        return $this->getText($this->start + $this->count() - 1);
    }

    public function getBulletText(HtmlLiChunk $chunk): string
    {
        return $this->getText($this->start + $this->indexOf($chunk));
    }

    /**
     * Gets the start counting.
     */
    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * Gets the numbered type.
     */
    public function getType(): HtmlListType
    {
        return $this->type;
    }

    /**
     * Sets the start counting (must be positive).
     */
    public function setStart(int $start): self
    {
        $this->start = \max($start, 1);

        return $this;
    }

    /**
     * Sets the numbered type.
     */
    public function setType(HtmlListType $type): self
    {
        $this->type = $type;

        return $this;
    }

    private function getText(int $number): string
    {
        return $this->type->getBulletText(\max($number, $this->start));
    }
}
