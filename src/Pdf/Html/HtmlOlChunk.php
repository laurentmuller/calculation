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
 * A specialized chunk for the HTML ordered list (ol).
 *
 * @see HtmlListType
 */
class HtmlOlChunk extends AbstractHtmlListChunk
{
    /**
     * @phpstan-param  positive-int $start
     */
    public function __construct(
        ?HtmlParentChunk $parent = null,
        ?string $className = null,
        private readonly int $start = 1,
        private readonly HtmlListType $type = HtmlListType::NUMBER
    ) {
        parent::__construct(HtmlTag::LIST_ORDERED, $parent, $className);
    }

    #[\Override]
    public function getBulletLast(): string
    {
        return $this->getText($this->start + $this->count() - 1);
    }

    #[\Override]
    public function getBulletText(HtmlLiChunk $chunk): string
    {
        return $this->getText($this->start + $this->indexOf($chunk));
    }

    /**
     * Gets the start counting.
     *
     * @phpstan-return positive-int
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

    private function getText(int $number): string
    {
        return $this->type->getBulletText(\max($number, $this->start));
    }
}
