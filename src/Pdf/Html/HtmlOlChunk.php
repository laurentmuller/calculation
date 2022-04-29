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
class HtmlOlChunk extends HtmlParentChunk
{
    /**
     * The start counting.
     */
    protected int $start = 1;

    /**
     * The numbered type.
     */
    protected HtmlListType $type = HtmlListType::NUMBER;

    /**
     * Constructor.
     *
     * @param string               $name   the tag name
     * @param HtmlParentChunk|null $parent the parent chunk
     */
    public function __construct(protected string $name, ?HtmlParentChunk $parent = null)
    {
        parent::__construct($name, $parent);
    }

    /**
     * Gets the bullet text for the given child.
     *
     * @param AbstractHtmlChunk $chunk the child chunk to get text for
     *
     * @return string the bullet text
     */
    public function getBulletChunk(AbstractHtmlChunk $chunk): string
    {
        return $this->getBulletText($this->indexOf($chunk) + 1);
    }

    /**
     * Gets the bullet text for this number of children (if any).
     */
    public function getBulletMaximum(): string
    {
        return $this->getBulletText($this->count());
    }

    /**
     * Gets the bullet text for the given index.
     * <b>N.B.:</b> If the index is smaller than or equal to 0, an empty string ('') is returned.
     *
     * @param int $index the list item index (1 based)
     *
     * @return string the bullet text
     */
    public function getBulletText(int $index): string
    {
        return $this->type->getBulletText($index + $this->start - 1);
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
     * Sets the start counting.
     *
     * <b>N.B.:</b> The minimum value is 1.
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
}
