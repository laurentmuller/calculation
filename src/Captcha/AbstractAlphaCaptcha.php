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

    #[\Override]
    public function checkAnswer(string $givenAnswer, string $expectedAnswer): bool
    {
        return StringUtils::equalIgnoreCase($givenAnswer, $expectedAnswer);
    }

    #[\Override]
    public function getChallenge(): Challenge
    {
        $word = $this->getRandomWord();
        $index = $this->getRandomIndex();
        $question = $this->getQuestion($word, $index);
        $answer = $this->getAnswer($word, $index);

        return new Challenge($question, $answer);
    }

    /**
     * Finds an answer within the given source.
     */
    protected function findAnswer(string $word, int $letterIndex, string $source): string
    {
        if ($letterIndex < 0) {
            $letterIndex = \abs($letterIndex) - 1;
            $word = \strrev($word);
        }
        $answer = '';
        for ($i = $letterIndex; $i >= 0; --$i) {
            $answer = $word[\strcspn($word, $source)];
            $word = StringUtils::pregReplace('/' . $answer . '/', '_', $word, 1);
        }

        return $answer;
    }

    /**
     * Gets the answer for the given word and letter index.
     */
    abstract protected function getAnswer(string $word, int $letterIndex): string;

    /**
     * Gets the mapping between the index and translatable letter.
     *
     * @return array<int, string>
     */
    abstract protected function getMapping(): array;

    /**
     * Gets the question for the given word and letter index.
     */
    protected function getQuestion(string $word, int $letterIndex): string
    {
        $parameters = [
            '%index%' => $this->getTranslatedIndex($letterIndex),
            '%letter%' => $this->getTranslatedLetter(),
            '%word%' => $word,
        ];

        return $this->trans('sentence', $parameters);
    }

    /**
     * Gets the random letter index used to get question and answer.
     */
    protected function getRandomIndex(): int
    {
        return \array_rand($this->getMapping());
    }

    /**
     * Gets a random word from the dictionary service.
     */
    protected function getRandomWord(): string
    {
        return $this->dictionary->getRandomWord();
    }

    /**
     * Gets the translated letter index.
     */
    protected function getTranslatedIndex(int $letterIndex): string
    {
        return $this->trans($this->getMapping()[$letterIndex]);
    }

    /**
     * Gets the translated letter name.
     */
    abstract protected function getTranslatedLetter(): string;

    /**
     * Translates the given message with the 'captcha' domain.
     */
    protected function trans(string $id, array $parameters = []): string
    {
        return $this->translator->trans($id, $parameters, 'captcha');
    }
}
