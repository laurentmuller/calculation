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

namespace App\Pdf\Traits;

use fpdf\PdfDocument;

/**
 * Trait to clean and encoding output texts.
 *
 * @phpstan-require-extends PdfDocument
 */
trait PdfCleanTextTrait
{
    /** The encoding source. */
    private const ENCODING_FROM = [
        'ASCII',
        'UTF-8',
        'CP1252',
        'ISO-8859-1',
    ];

    /** The encoding target. */
    private const ENCODING_TO = 'CP1252';

    protected function cleanText(string $str): string
    {
        $str = parent::cleanText($str);
        if ('' === $str) {
            return $str;
        }

        return parent::convertEncoding($str, self::ENCODING_TO, self::ENCODING_FROM);
    }
}
