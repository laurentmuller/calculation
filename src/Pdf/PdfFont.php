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
 * Define a font style.
 *
 * @author Laurent Muller
 */
class PdfFont implements IPdfDocumentUpdater
{
    /**
     * The default font name (Arial).
     */
    public const DEFAULT_NAME = self::NAME_ARIAL;

    /**
     * The default font size (9pt).
     */
    public const DEFAULT_SIZE = 9.0;

    /**
     * The default font style (Regular).
     */
    public const DEFAULT_STYLE = self::STYLE_REGULAR;

    /**
     * The Arial font name (synonymous; sans serif).
     */
    public const NAME_ARIAL = 'Arial';

    /**
     * The Courier font name (fixed-width).
     */
    public const NAME_COURIER = 'Courier';

    /**
     * The Helvetica font name (synonymous; sans serif).
     */
    public const NAME_HELVETICA = 'Helvetica';

    /**
     * The Symbol font name (symbolic).
     */
    public const NAME_SYMBOL = 'Symbol';

    /**
     * The Times font name (serif).
     */
    public const NAME_TIMES = 'Times';

    /**
     * The ZapfDingbats font name (symbolic).
     */
    public const NAME_ZAPFDINGBATS = 'ZapfDingbats';

    /**
     * The bold font style. Not allowed for <code>Symbol</code> and <code>ZapfDingbats</code> fonts.
     */
    public const STYLE_BOLD = 'B';

    /**
     * The italic font style. Not allowed for <code>Symbol</code> and <code>ZapfDingbats</code> fonts.
     */
    public const STYLE_ITALIC = 'I';

    /**
     * The regular font style.
     */
    public const STYLE_REGULAR = '';

    /**
     * The uderline font style.
     */
    public const STYLE_UNDERLINE = 'U';

    /**
     * The name.
     *
     * @var string
     */
    protected $name;

    /**
     * The size.
     *
     * @var float
     */
    protected $size;

    /**
     * The style.
     *
     * @var string
     */
    protected $style;

    /**
     * Constructor.
     *
     * @param string $name  the name
     * @param float  $size  the size
     * @param string $style the style
     */
    public function __construct(string $name = self::DEFAULT_NAME, float $size = self::DEFAULT_SIZE, string $style = self::DEFAULT_STYLE)
    {
        $this->setName($name)
            ->setStyle($style)
            ->setSize($size);
    }

    public function __toString()
    {
        $name = Utils::getShortName($this);
        $style = $this->getTextStyle();
        if (empty($style)) {
            return \sprintf('%s(%s, %gpt)', $name, $this->name, $this->size);
        }

        return \sprintf('%s(%s, %gpt %s)', $name, $this->name, $this->size, $style);
    }

    /**
     * Adds the given style, if not present, to this style.
     *
     * @param string $style the style to add
     */
    public function addStyle(string $style): self
    {
        $style = \strtoupper($style);
        for ($i = 0, $len = \strlen($style); $i < $len; ++$i) {
            switch ($style[$i]) {
                case self::STYLE_BOLD:
                case self::STYLE_ITALIC:
                case self::STYLE_UNDERLINE:
                    if (false === \strpos($this->style, $style[$i])) {
                        $this->style .= $style[$i];
                    }
                    break;
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(PdfDocument $doc): void
    {
        $doc->SetFont($this->name, $this->style, $this->size);
    }

    /**
     * Sets the font style to bold.
     *
     * @param bool $add true to add bold to existing style, false to replace
     */
    public function bold(bool $add = false): self
    {
        if ($add) {
            return $this->addStyle(self::STYLE_BOLD);
        }

        return $this->setStyle(self::STYLE_BOLD);
    }

    /**
     * Gets the default font.
     */
    public static function default(): self
    {
        return new self();
    }

    /**
     * Gets the font name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the font size.
     *
     * @return number
     */
    public function getSize(): float
    {
        return $this->size;
    }

    /**
     * Gets the font style.
     *
     * @return string
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * Returns if this font use the default size.
     *
     * @return bool true if default size
     */
    public function isDefaultSize()
    {
        return self::DEFAULT_SIZE === $this->size;
    }

    /**
     * Sets the font style to italic.
     *
     * @param bool $add true to add italic to existing style, false to replace
     */
    public function italic(bool $add = false): self
    {
        if ($add) {
            return $this->addStyle(self::STYLE_ITALIC);
        }

        return $this->setStyle(self::STYLE_ITALIC);
    }

    /**
     * Sets the font style to regular.
     */
    public function regular(): self
    {
        return $this->setStyle(self::STYLE_REGULAR);
    }

    /**
     * Reset all properties to the default values.
     *
     * @return self this instance
     */
    public function reset(): self
    {
        $this->name = self::DEFAULT_NAME;
        $this->style = self::DEFAULT_STYLE;
        $this->size = self::DEFAULT_SIZE;

        return $this;
    }

    /**
     * Sets the font name.
     *
     * @param string $name the name or null for default
     *
     * @return self this instance
     */
    public function setName(?string $name): self
    {
        $this->name = empty($name) ? self::DEFAULT_NAME : $name;

        return $this;
    }

    /**
     * Sets the font size.
     *
     * @param float $size the size to set
     *
     * @return self this instance
     */
    public function setSize(float $size): self
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Sets the font style.
     *
     * @return self this instance
     */
    public function setStyle(string $style): self
    {
        // reset
        $this->style = self::STYLE_REGULAR;

        // update
        return $this->addStyle($style);
    }

    /**
     * Sets the font style to underline.
     *
     * @param bool $add true to add bold to existing style, false to replace
     */
    public function underline(bool $add = false): self
    {
        if ($add) {
            return $this->addStyle(self::STYLE_UNDERLINE);
        }

        return $this->setStyle(self::STYLE_UNDERLINE);
    }

    /**
     * Gets the textual representation of this style.
     *
     * @return string this style
     */
    private function getTextStyle()
    {
        $result = [];
        $style = $this->style;
        if (false !== \stripos($style, self::STYLE_BOLD)) {
            $result[] = 'Bold';
        }
        if (false !== \stripos($style, self::STYLE_ITALIC)) {
            $result[] = 'Italic';
        }
        if (false !== \stripos($style, self::STYLE_UNDERLINE)) {
            $result[] = 'Underline';
        }

        return \implode(' ', $result);
    }
}
