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

namespace App\Pdf;

use App\Pdf\Enums\PdfTextAlignment;
use App\Util\Utils;

/**
 * Represent a group in the grouping table.
 *
 * @see PdfGroupTableBuilder
 */
class PdfGroup implements PdfDocumentUpdaterInterface
{
    /**
     * The border style.
     */
    protected PdfBorder $border;

    /**
     * The style.
     */
    protected ?PdfStyle $style;

    /**
     * Constructor.
     *
     * @param ?mixed           $key       the group key
     * @param PdfTextAlignment $alignment the group alignment
     * @param ?PdfBorder       $border    the group border or null to use default
     * @param ?PdfStyle        $style     the group style or null to use default
     */
    public function __construct(protected mixed $key = null, protected PdfTextAlignment $alignment = PdfTextAlignment::LEFT, ?PdfBorder $border = null, ?PdfStyle $style = null)
    {
        $this->border = $border ?? PdfBorder::all();
        $this->style = $style ?? PdfStyle::getCellStyle()->setFontBold();
    }

    /**
     * {@inheritdoc}
     */
    public function apply(PdfDocument $doc): void
    {
        $this->style?->apply($doc);
    }

    /**
     * Gets the group alignment.
     */
    public function getAlignment(): PdfTextAlignment
    {
        return $this->alignment;
    }

    /**
     * Gets the border.
     */
    public function getBorder(): PdfBorder
    {
        return $this->border;
    }

    /**
     * Gets the key.
     */
    public function getKey(): mixed
    {
        return $this->key;
    }

    /**
     * Gets the name.
     */
    public function getName(): ?string
    {
        /** @psalm-var mixed $key */
        $key = $this->key;
        if (\is_scalar($key) || (\is_object($key) && \method_exists($key, '__toString'))) {
            return (string) $key;
        }

        return null;
    }

    /**
     * Gets the style.
     */
    public function getStyle(): ?PdfStyle
    {
        return $this->style;
    }

    /**
     * Returns if the key is not empty.
     */
    public function isKey(): bool
    {
        return Utils::isString($this->getName());
    }

    /**
     * Output this group to the given parent table.
     */
    public function output(PdfGroupTableBuilder $parent): void
    {
        $oldBorder = $parent->getBorder();
        $parent->setBorder($this->border);
        $parent->singleLine($this->getName(), $this->getStyle(), $this->getAlignment());
        $parent->setBorder($oldBorder);
    }

    /**
     * Sets the alignment.
     */
    public function setAlignment(PdfTextAlignment $alignment): self
    {
        $this->alignment = $alignment;

        return $this;
    }

    /**
     * Sets the border.
     */
    public function setBorder(PdfBorder|string|int $border): self
    {
        $this->border = \is_string($border) || \is_int($border) ? new PdfBorder($border) : $border;

        return $this;
    }

    /**
     * Sets the key.
     */
    public function setKey(mixed $key): self
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Sets the style.
     */
    public function setStyle(?PdfStyle $style): self
    {
        $this->style = $style;

        return $this;
    }
}
