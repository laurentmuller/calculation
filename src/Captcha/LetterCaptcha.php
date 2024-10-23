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

namespace App\Captcha;

/**
 * Alpha captcha to validate a letter.
 */
class LetterCaptcha extends AbstractAlphaCaptcha
{
    private const MAPPING = [
        0 => 'first',
        1 => 'second',
        2 => 'third',
        3 => 'fourth',
        4 => 'fifth',
        -1 => 'last',
    ];

    protected function getAnswer(string $word, int $letterIndex): string
    {
        if ($letterIndex < 0) {
            $letterIndex = \abs($letterIndex) - 1;
            $word = \strrev($word);
        }

        return $word[$letterIndex];
    }

    protected function getMapping(): array
    {
        return self::MAPPING;
    }

    protected function getTranslatedLetter(): string
    {
        return $this->trans('letter');
    }
}
