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
 * Alpha captcha to validate a consonant.
 */
class ConsonantCaptcha extends AbstractAlphaCaptcha
{
    private const MAPPING = [
        0 => 'first',
        1 => 'second',
        2 => 'third',
        -1 => 'last',
    ];

    private const SOURCE = 'BCDFGHJKLMNPQRSTVWXZ';

    protected function getAnswer(string $word, int $letterIndex): string
    {
        return $this->findAnswer($word, $letterIndex, self::SOURCE);
    }

    protected function getMapping(): array
    {
        return self::MAPPING;
    }

    protected function getTranslatedLetter(): string
    {
        return $this->trans('consonant');
    }
}
