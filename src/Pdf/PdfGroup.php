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

namespace App\Pdf;

use App\Util\Utils;

/**
 * Represent a group in the grouping table.
 *
 * @author Laurent Muller
 *
 * @see \App\Pdf\PdfGroupTableBuilder
 */
class PdfGroup implements PdfDocumentUpdaterInterface, PdfConstantsInterface
{
    use PdfAlignmentTrait;
    use PdfBorderTrait;

    /**
     * The key.
     *
     * @var mixed
     */
    protected $key;

    /**
     * The style.
     *
     * @var PdfStyle
     */
    protected $style;

    /**
     * Constructor.
     *
     * @param mixed    $key       the group key
     * @param string   $alignment the group alignment
     * @param mixed    $border    the group border
     * @param PdfStyle $style     the group style or null for default style
     */
    public function __construct($key = null, string $alignment = self::ALIGN_LEFT, $border = self::BORDER_ALL, ?PdfStyle $style = null)
    {
        $this->setKey($key)
            ->setAlignment($alignment)
            ->setBorder($border)
            ->setStyle($style ?: PdfStyle::getCellStyle()->setFontBold());
    }

    /**
     * {@inheritdoc}
     */
    public function apply(PdfDocument $doc): void
    {
        if (null !== $this->style) {
            $this->style->apply($doc);
        }
    }

    /**
     * Gets the key.
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Gets the name.
     */
    public function getName(): ?string
    {
        $key = $this->key;
        if (\is_scalar($key) || (\is_object($key) && \method_exists($key, '__toString'))) {
            return (string) $key;
        }

        return null;
    }

    /**
     * Gets the style.
     *
     * @return \App\Pdf\PdfStyle
     */
    public function getStyle(): ?PdfStyle
    {
        return $this->style;
    }

    /**
     * Returns if the key is not empty.
     *
     * @return bool true if not empty
     */
    public function isKey(): bool
    {
        return Utils::isString($this->getName());
    }

    /**
     * Output this group.
     *
     * @param PdfGroupTableBuilder $parent the parent table
     */
    public function output(PdfGroupTableBuilder $parent): void
    {
        $oldBorder = $parent->getBorder();
        $parent->setBorder($this->border);
        $parent->singleLine($this->getName(), $this->getStyle(), $this->getAlignment());
        $parent->setBorder($oldBorder);
    }

    /**
     * Sets the key.
     */
    public function setKey($key): self
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
