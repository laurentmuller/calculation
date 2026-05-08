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

use App\Utils\StringUtils;

/**
 * A PDF label item.
 */
readonly class PdfLabelItem
{
    public function __construct(public ?string $text, public ?PdfStyle $style = null)
    {
    }

    public static function instance(?string $text, ?PdfStyle $style = null): self
    {
        return new self($text, $style);
    }

    /**
     * @phpstan-assert-if-true non-empty-string $this->text
     */
    public function isText(): bool
    {
        return StringUtils::isString($this->text);
    }
}
