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
    private const INDEX_MAPPING = [
        '0' => 'first',
        '1' => 'second',
        '2' => 'third',
        '-1' => 'last',
    ];

    private const VOWEL = 'AEIOUY';

    protected function getAnswer(string $word, int $letterIndex): string
    {
        return $this->findAnswer($word, $letterIndex, self::VOWEL);
    }

    protected function getLetterIndex(): int
    {
        return \array_rand(self::INDEX_MAPPING);
    }

    protected function getQuestion(string $word, int $letterIndex): string
    {
        $parameters = [
            '%index%' => $this->trans(self::INDEX_MAPPING[$letterIndex]),
            '%letter%' => $this->trans('vowel'),
            '%word%' => $word,
        ];

        return $this->trans('sentence', $parameters);
    }
}
