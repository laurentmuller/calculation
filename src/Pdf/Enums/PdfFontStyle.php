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

namespace App\Pdf\Enums;

use App\Interfaces\EnumDefaultInterface;
use App\Traits\EnumDefaultTrait;
use Elao\Enum\Attribute\EnumCase;

/**
 * The PDF font style enumeration.
 *
 * @implements EnumDefaultInterface<PdfFontStyle>
 */
enum PdfFontStyle: string implements EnumDefaultInterface
{
    use EnumDefaultTrait;

    /*
     * Bold.
     *
     * Not allowed for <code>Symbol</code> and <code>ZapfDingbats</code> fonts.
     */
    case BOLD = 'B';

    /*
     * Bold and italic.
     *
     * Not allowed for <code>Symbol</code> and <code>ZapfDingbats</code> fonts.
     */
    case BOLD_ITALIC = 'BI';

    /*
     * Bold, italic and underline.
     *
     * Not allowed for <code>Symbol</code> and <code>ZapfDingbats</code> fonts.
     */
    case BOLD_ITALIC_UNDERLINE = 'BIU';

    /*
     * Bold and underline.
     *
     * Not allowed for <code>Symbol</code> and <code>ZapfDingbats</code> fonts.
     */
    case BOLD_UNDERLINE = 'BU';

    /*
     * Italic.
     *
     * Not allowed for <code>Symbol</code> and <code>ZapfDingbats</code> fonts.
     */
    case ITALIC = 'I';

    /*
     * Italic and underline.
     *
     * Not allowed for <code>Symbol</code> and <code>ZapfDingbats</code> fonts.
     */
    case ITALIC_UNDERLINE = 'IU';

    /*
     * Regular (default).
     */
    #[EnumCase(extras: [EnumDefaultInterface::NAME => true])]
    case REGULAR = '';

    /*
     * Underline.
     */
    case UNDERLINE = 'U';

    /**
     * Converts the given string (if any) to a font style.
     *
     * If no match, the {@link PdfFontStyle::REGULAR} is returned.
     */
    public static function fromStyle(?string $style): self
    {
        if (null === $style || '' === $style) {
            return self::REGULAR;
        }
        /** @psalm-var string[] $values */
        static $values = [];
        if ([] === $values) {
            $values = [
               self::BOLD->value,
               self::ITALIC->value,
               self::UNDERLINE->value,
        ];
        }
        $result = '';
        $style = \strtoupper($style);
        foreach ($values as $value) {
            $result .= \str_contains($style, $value) ? $value : '';
        }

        return self::from($result);
    }
}
