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
use fpdf\PdfBorder;

/**
 * Class to parse margins and paddings from an HTML Boostrap class.
 *
 * @see https://getbootstrap.com/docs/5.3/utilities/spacing/
 */
class HtmlSpacing extends PdfBorder
{
    /** The pattern to extract margins or paddings. */
    private const string MARGINS_PATTERN = '/^[mp]([tbsexy])?-(sm-|md-|lg-|xl-|xxl-)?([012345])/im';

    public function __construct(
        public int $size = 0,
        bool $left = false,
        bool $top = false,
        bool $right = false,
        bool $bottom = false,
    ) {
        parent::__construct($left, $top, $right, $bottom);
    }

    /**
     * Parse the given HTML class and returns a new instance; if success. Return <code>null</code> if the class cannot
     * be parsed.
     */
    public static function parse(string $class): ?self
    {
        if (!StringUtils::pregMatch(self::MARGINS_PATTERN, \strtolower($class), $matches)) {
            return null;
        }

        $size = (int) $matches[3];

        return match ($matches[1]) {
            's' => new self($size, left: true),
            't' => new self($size, top: true),
            'e' => new self($size, right: true),
            'b' => new self($size, bottom: true),
            'x' => new self($size, left: true, right: true),
            'y' => new self($size, top: true, bottom: true),
            default => new self($size, left: true, top: true, right: true, bottom: true),
        };
    }
}
