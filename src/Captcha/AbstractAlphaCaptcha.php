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

use App\Service\DictionaryService;
use App\Utils\StringUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract implementation of the alpha captcha interface.
 */
abstract class AbstractAlphaCaptcha implements AlphaCaptchaInterface
{
    public function __construct(
        private readonly DictionaryService $dictionary,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function checkAnswer(string $givenAnswer, string $expectedAnswer): bool
    {
        return StringUtils::equalIgnoreCase($givenAnswer, $expectedAnswer);
    }

    public function getChallenge(): array
    {
        $word = $this->getRandomWord();
        $letterIndex = $this->getLetterIndex();

        return [
            $this->getQuestion($word, $letterIndex),
            $this->getAnswer($word, $letterIndex),
        ];
    }

    /**
     * Finds an answer within the given source.
     *
     * @param string $source the source string to search in
     */
    protected function findAnswer(string $word, int $letterIndex, string $source): string
    {
        if (0 > $letterIndex) {
            $letterIndex = \abs($letterIndex) - 1;
            $word = \strrev($word);
        }
        $answer = '';
        for ($i = $letterIndex; $i >= 0; --$i) {
            $answer = $word[\strcspn($word, $source)];
            $word = (string) \preg_replace('/' . $answer . '/', '_', $word, 1);
        }

        return $answer;
    }

    /**
     * Gets the answer for the given word and letter index.
     */
    abstract protected function getAnswer(string $word, int $letterIndex): string;

    /**
     * Gets the letter index used to get question and answer.
     */
    abstract protected function getLetterIndex(): int;

    /**
     * Gets the question for the given word and letter index.
     */
    abstract protected function getQuestion(string $word, int $letterIndex): string;

    /**
     * Gets a random word from the dictionary service.
     */
    protected function getRandomWord(): string
    {
        return $this->dictionary->getRandomWord();
    }

    /**
     * Translates the given message with the 'captcha' domain.
     */
    protected function trans(string $id, array $parameters = []): string
    {
        return $this->translator->trans($id, $parameters, 'captcha');
    }
}
