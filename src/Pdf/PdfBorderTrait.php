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

/**
 * This trait allows class to have a border property.
 * The default value is <code>IPdfConstants::BORDER_ALL</code>.
 *
 * @author Laurent Muller
 */
trait PdfBorderTrait
{
    /**
     * The border style.
     *
     * @var mixed
     */
    protected $border = IPdfConstants::BORDER_ALL;

    /**
     * Gets the border style.
     *
     * @return int|string How the borders must be drawn around the cells.The value can be either:
     *                    <ul>
     *                    <li>A number:
     *                    <ul>
     *                    <li><b>0</b> : No border (default value).</li>
     *                    <li><b>1</b> : Frame.</li>
     *                    </ul>
     *                    </li>
     *                    <li>A string containing some or all of the following characters (in any order):
     *                    <ul>
     *                    <li>'<b>L</b>' : Left.</li>
     *                    <li>'<b>T</b>' : Top.</li>
     *                    <li>'<b>R</b>' : Right.</li>
     *                    <li>'<b>B</b>' : Bottom.</li>
     *                    </ul>
     *                    </li>
     *                    </ul>
     */
    public function getBorder()
    {
        return $this->border;
    }

    /**
     * Returns if this border is inherited.
     *
     * @return bool true if inherited
     */
    public function isBorderInherited(): bool
    {
        return IPdfConstants::BORDER_INHERITED === $this->border;
    }

    /**
     * Sets the border style.
     *
     * @param int|string $border indicates if borders must be drawn around the cell. The value can be either:
     *                           <ul>
     *                           <li>A number:
     *                           <ul>
     *                           <li><b>0</b> : No border (default value).</li>
     *                           <li><b>1</b> : Frame.</li>
     *                           </ul>
     *                           </li>
     *                           <li>A string containing some or all of the following characters (in any order):
     *                           <ul>
     *                           <li>'<b>L</b>' : Left.</li>
     *                           <li>'<b>T</b>' : Top.</li>
     *                           <li>'<b>R</b>' : Right.</li>
     *                           <li>'<b>B</b>' : Bottom.</li>
     *                           </ul>
     *                           </li>
     *                           </ul>
     *
     * @return self this instance
     */
    public function setBorder($border)
    {
        $this->border = $this->validateBorder($border);

        return $this;
    }

    /**
     * Gets the textual representation of this border.
     *
     * @return string the textual representation
     */
    protected function getBorderText(): string
    {
        $border = $this->border;

        $result = [];
        if (empty($border)) {
            $result[] = 'None';
        } elseif (IPdfConstants::BORDER_ALL === $border) {
            $result[] = 'All';
        } elseif (IPdfConstants::BORDER_INHERITED === $border) {
            $result[] = 'Inherited';
        } else {
            for ($i = 0; $i < \strlen($border); ++$i) {
                switch ($border[$i]) {
                    case IPdfConstants::BORDER_LEFT:
                        $result[] = 'Left';
                        break;

                    case IPdfConstants::BORDER_RIGHT:
                        $result[] = 'Right';
                        break;

                    case IPdfConstants::BORDER_TOP:
                        $result[] = 'Top';
                        break;

                    case IPdfConstants::BORDER_BOTTOM:
                        $result[] = 'Bottom';
                        break;
                }
            }
        }
        if (empty($result)) {
            $result[] = 'None';
        }

        return 'PdfBorder('.\implode(' ', $result).')';
    }

    /**
     * Validate the given border.
     *
     * @param mixed $border the border to validate
     *
     * @return mixed a valid border
     */
    protected function validateBorder($border)
    {
        if (empty($border)) {
            return IPdfConstants::BORDER_NONE;
        }
        if (IPdfConstants::BORDER_ALL === $border) {
            return IPdfConstants::BORDER_ALL;
        }
        if (IPdfConstants::BORDER_INHERITED === $border) {
            return IPdfConstants::BORDER_INHERITED;
        }

        $result = '';
        $border = \strtoupper((string) $border);
        for ($i = 0; $i < \strlen($border); ++$i) {
            switch ($border[$i]) {
                case IPdfConstants::BORDER_LEFT:
                case IPdfConstants::BORDER_RIGHT:
                case IPdfConstants::BORDER_TOP:
                case IPdfConstants::BORDER_BOTTOM:
                    if (false === \strpos($result, $border[$i])) {
                        $result .= $border[$i];
                    }
                    break;
            }
        }

        return $result ?: IPdfConstants::BORDER_NONE;
    }
}
