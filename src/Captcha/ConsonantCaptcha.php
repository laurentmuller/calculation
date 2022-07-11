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
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ConsonantCaptcha extends AbstractAlphaCaptcha
{
    private const CONSONANT = 'BCDFGHJKLMNPQRSTVWXZ';

    private const INDEX_MAPPING = [
        '0' => 'first',
        '1' => 'second',
        '2' => 'third',
        '-1' => 'last',
    ];

    /**
     * Gets the default index name.
     */
    public static function getDefaultIndexName(): string
    {
        return 'ConsonantCaptcha';
    }

    /**
     * {@inheritDoc}
     */
    protected function getAnswer(string $word, int $letterIndex): string
    {
        return $this->findAnswer($word, $letterIndex, self::CONSONANT);
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
            '%index%' => $this->trans(self::INDEX_MAPPING[$letterIndex], [], 'captcha'),
            '%letter%' => $this->trans('consonant', [], 'captcha'),
            '%word%' => $word,
        ];

        return $this->trans('sentence', $params, 'captcha');
    }
}
