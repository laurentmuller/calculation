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

use App\Utils\StringUtils;

/**
 * Class to parse margins and paddings from an HTML Boostrap class.
 *
 * @see https://getbootstrap.com/docs/5.3/utilities/spacing/
 */
readonly class HtmlSpacing
{
    /**
     * The pattern to extract margins and paddings.
     */
    private const MARGINS_PATTERN = '/^[mp]([tbsexy])?-(sm-|md-|lg-|xl-|xxl-)?([012345])/im';

    /**
     * @param int  $size   the size
     * @param bool $top    the top side state
     * @param bool $bottom the bottom side state
     * @param bool $left   the left side state
     * @param bool $right  the right side state
     */
    public function __construct(
        public int $size = 0,
        public bool $top = false,
        public bool $bottom = false,
        public bool $left = false,
        public bool $right = false,
    ) {
    }

    /**
     * Parse the given HTML class and returns a spacing instance; returns <code>null</code> if
     * the class cannot be parsed.
     */
    public static function instance(string $class): ?self
    {
        if (!StringUtils::pregMatchAll(self::MARGINS_PATTERN, $class, $matches, \PREG_SET_ORDER)) {
            return null;
        }

        $match = $matches[0];
        $size = (int) $match[3];
        $top = $bottom = $left = $right = false;
        switch ($match[1]) {
            case 't':
                $top = true;
                break;
            case 'b':
                $bottom = true;
                break;
            case 's': // start
                $left = true;
                break;
            case 'e': // end
                $right = true;
                break;
            case 'x':
                $left = $right = true;
                break;
            case 'y':
                $top = $bottom = true;
                break;
            default: // all
                $left = $right = $top = $bottom = true;
                break;
        }

        return new self($size, $top, $bottom, $left, $right);
    }

    /**
     * Returns if all four sides are set.
     */
    public function isAll(): bool
    {
        return $this->left && $this->right && $this->top && $this->bottom;
    }

    /**
     * Returns if no one side is set.
     */
    public function isNone(): bool
    {
        return !$this->left && !$this->right && !$this->top && !$this->bottom;
    }
}
