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
 * Alpha captcha to validate a vowel.
 */
class VowelCaptcha extends AbstractAlphaCaptcha
{
    private const array MAPPING = [
        0 => 'first',
        1 => 'second',
        2 => 'third',
        -1 => 'last',
    ];

    private const string SOURCE = 'AEIOUY';

    #[\Override]
    protected function getAnswer(string $word, int $letterIndex): string
    {
        return $this->findAnswer($word, $letterIndex, self::SOURCE);
    }

    #[\Override]
    protected function getMapping(): array
    {
        return self::MAPPING;
    }

    #[\Override]
    protected function getTranslatedLetter(): string
    {
        return $this->trans('vowel');
    }
}
