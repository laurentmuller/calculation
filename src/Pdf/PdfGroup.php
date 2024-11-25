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

use App\Pdf\Interfaces\PdfDocumentUpdaterInterface;
use App\Utils\StringUtils;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfDocument;

/**
 * Represent a group in the grouping table.
 *
 * @see PdfGroupTable
 */
class PdfGroup implements PdfDocumentUpdaterInterface
{
    /**
     * The style.
     */
    private PdfStyle $style;

    /**
     * @param ?mixed           $key       the group key
     * @param PdfTextAlignment $alignment the group alignment
     * @param ?PdfStyle        $style     the group style or null to use default
     */
    public function __construct(
        private mixed $key = null,
        private PdfTextAlignment $alignment = PdfTextAlignment::LEFT,
        ?PdfStyle $style = null
    ) {
        $this->style = $style ?? PdfStyle::getCellStyle()->setFontBold();
    }

    public function apply(PdfDocument $doc): void
    {
        $this->style->apply($doc);
    }

    /**
     * Gets the group alignment.
     */
    public function getAlignment(): PdfTextAlignment
    {
        return $this->alignment;
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
    public function getStyle(): PdfStyle
    {
        return $this->style;
    }

    /**
     * Returns if the key is not empty.
     */
    public function hasKey(): bool
    {
        return StringUtils::isString($this->getName());
    }

    /**
     * Output this group to the given parent table.
     */
    public function output(PdfGroupTable $parent): void
    {
        $oldBorder = $parent->getBorder();
        $parent->singleLine($this->getName(), $this->getStyle(), $this->getAlignment());
        $parent->setBorder($oldBorder);
    }

    /**
     * Sets the alignment.
     */
    public function setAlignment(PdfTextAlignment $alignment): static
    {
        $this->alignment = $alignment;

        return $this;
    }

    /**
     * Sets the key.
     */
    public function setKey(mixed $key): static
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Sets the style.
     */
    public function setStyle(PdfStyle $style): static
    {
        $this->style = $style;

        return $this;
    }
}
