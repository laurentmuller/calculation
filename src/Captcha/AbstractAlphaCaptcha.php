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

use App\Service\DictionnaryService;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract implementation of the alpha captcha interface.
 *
 * @author Laurent Muller
 */
abstract class AbstractAlphaCaptcha implements AlphaCaptchaInterface
{
    /**
     * The dictionnary service to get random word.
     */
    protected DictionnaryService $dictionnary;

    /**
     * The translator.
     */
    protected TranslatorInterface $translator;

    /**
     * Constructor.
     */
    public function __construct(DictionnaryService $dictionnary, TranslatorInterface $translator)
    {
        $this->dictionnary = $dictionnary;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function checkAnswer(string $givenAnswer, string $expectedAnswer): bool
    {
        return \strtoupper($givenAnswer) === \strtoupper($expectedAnswer);
    }

    /**
     * {@inheritDoc}
     */
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
     * Gets a random word from the dictionnary service.
     */
    protected function getRandomWord(): string
    {
        return $this->dictionnary->getRandomWord();
    }

    /**
     * Translates the given message.
     */
    protected function trans(string $id, array $parameters = [], string $domain = 'captcha', string $locale = null): string
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }
}
