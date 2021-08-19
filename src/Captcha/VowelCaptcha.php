<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Captcha;

/**
 * Alpha captcha to validate a vowel.
 *
 * @author Laurent Muller
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

    /**
     * {@inheritDoc}
     */
    protected function getAnswer(string $word, int $letterIndex): string
    {
        if (0 > $letterIndex) {
            $letterIndex = \abs($letterIndex) - 1;
            $word = \strrev($word);
        }

        $answer = null;

        for ($i = $letterIndex; $i >= 0; --$i) {
            $answer = $word[\strcspn($word, self::VOWEL)];
            $word = \preg_replace('/' . $answer . '/', '_', $word, 1);
        }

        return (string) $answer;
    }

    /**
     * {@inheritDoc}
     */
    protected function getLetterIndex(): int
    {
        return \array_rand(self::INDEX_MAPPING);
    }

    /**
     * {@inheritDoc}
     */
    protected function getQuestion(string $word, int $letterIndex): string
    {
        $params = [
            '%index%' => $this->trans(self::INDEX_MAPPING[$letterIndex]),
            '%letter%' => $this->trans('vowel'),
            '%word%' => $word,
        ];

        return $this->trans('sentence', $params);
    }
}
