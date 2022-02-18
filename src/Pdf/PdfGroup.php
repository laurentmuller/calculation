<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     */
    protected ?PdfStyle $style = null;

    /**
     * Constructor.
     *
     * @param mixed      $key       the group key
     * @param string     $alignment the group alignment
     * @param int|string $border    the group border
     * @param PdfStyle   $style     the group style or null for default style
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
     *
     * @return mixed
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
        /** @psalm-var mixed $key */
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
     *
     * @param mixed $key
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
