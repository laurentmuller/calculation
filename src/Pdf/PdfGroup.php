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

use App\Utils\Utils;

/**
 * Represent a group in the grouping table.
 *
 * @author Laurent Muller
 *
 * @see \App\Pdf\PdfGroupTableBuilder
 */
class PdfGroup implements IPdfDocumentUpdater, IPdfConstants
{
    use PdfAlignmentTrait;
    use PdfBorderTrait;

    /**
     * The name.
     *
     * @var string
     */
    protected $name;

    /**
     * The style.
     *
     * @var PdfStyle
     */
    protected $style;

    /**
     * Constructor.
     *
     * @param string   $name      the group name
     * @param string   $alignment the group alignment
     * @param mixed    $border    the group border
     * @param PdfStyle $style     the group style or null for default style
     */
    public function __construct(?string $name = null, string $alignment = self::ALIGN_LEFT, $border = self::BORDER_ALL, ?PdfStyle $style = null)
    {
        $this->setName($name)
            ->setAlignment($alignment)
            ->setBorder($border)
            ->setStyle($style ?: PdfStyle::getCellStyle()->setFontBold());
    }

    /**
     * {@inheritdoc}
     */
    public function apply(PdfDocument $doc): void
    {
        $this->style->apply($doc);
    }

    /**
     * Gets the name.
     *
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
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
     * Returns if the name is not empty.
     *
     * @return bool true if not empty
     */
    public function isName(): bool
    {
        return Utils::isString($this->name);
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
        $parent->singleLine($this->name, $this->style, $this->alignment);
        $parent->setBorder($oldBorder);
    }

    /**
     * Sets the name.
     *
     * @param string $name
     *
     * @return self this instance
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the style.
     *
     * @param \App\Pdf\PdfStyle $style
     *
     * @return self this instance
     */
    public function setStyle(?PdfStyle $style): self
    {
        $this->style = $style;

        return $this;
    }
}
