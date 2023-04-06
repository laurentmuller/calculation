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

namespace App\Service;

use App\Enums\StrengthLevel;
use App\Traits\StrengthLevelTranslatorTrait;
use Createnl\ZxcvbnBundle\ZxcvbnFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZxcvbnPhp\Zxcvbn;

/**
 * Service to validate a password with Zxcvbn.
 */
class PasswordService
{
    use StrengthLevelTranslatorTrait;

    private ?Zxcvbn $service = null;

    /**
     * Constructor.
     */
    public function __construct(private readonly ZxcvbnFactoryInterface $factory, private readonly TranslatorInterface $translator)
    {
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Validate the given password.
     *
     * @param string  $password the password to validate
     * @param int     $strength the minimum level
     * @param ?string $email    the optional user's email
     * @param ?string $user     the optional user's name
     *
     * @return array the validation result where the 'result' is boolean indicate, when true; the success
     */
    public function validate(string $password, int $strength, ?string $email = null, ?string $user = null): array
    {
        if (null !== $response = $this->validatePassword($password)) {
            return $response;
        }
        $results = $this->getPasswordStrength($password, \array_filter([$email, $user]));
        if (null !== $response = $this->validateScoreResults($results)) {
            return $response;
        }
        $scoreLevel = StrengthLevel::from($results['score']);
        $results = \array_merge($results, [
            'score' => [
                'value' => $scoreLevel->value,
                'percent' => $scoreLevel->percent(),
                'text' => $this->translateLevel($scoreLevel),
            ],
        ]);
        if (null !== $response = $this->validateStrength($strength, $results)) {
            return $response;
        }
        $minimumLevel = StrengthLevel::from($strength);
        $results = \array_merge($results, [
            'minimum' => [
                'value' => $minimumLevel->value,
                'percent' => $minimumLevel->percent(),
                'text' => $this->translateLevel($minimumLevel),
            ],
        ]);
        if (null !== $response = $this->validateMinimumLevel($minimumLevel, $results)) {
            return $response;
        }
        if (null !== $response = $this->validateScoreLevel($minimumLevel, $scoreLevel, $results)) {
            return $response;
        }

        return \array_merge(['result' => true], $results);
    }

    private function getFalseResult(string $message, array $values = []): array
    {
        return \array_merge(['result' => false, 'message' => $message], $values);
    }

    /**
     * @return array{score: int, warning?: string, suggestions?: string[]}
     */
    private function getPasswordStrength(string $password, array $userInputs): array
    {
        /** @psalm-var array{score: int, feedback: array{warning: string, suggestions: string[]}} $result */
        $result = $this->getService()->passwordStrength($password, $userInputs);

        return \array_merge(
            ['score' => $result['score']],
            \array_filter([
                'warning' => $result['feedback']['warning'],
                'suggestions' => $result['feedback']['suggestions'],
            ]),
        );
    }

    private function getService(): Zxcvbn
    {
        return $this->service ??= $this->factory->createZxcvbn();
    }

    private function validateMinimumLevel(StrengthLevel $minimumLevel, array $results): ?array
    {
        if (StrengthLevel::NONE === $minimumLevel) {
            $message = $this->trans('password.strength_disabled', [], 'validators');

            return $this->getFalseResult($message, $results);
        }

        return null;
    }

    private function validatePassword(string $password): ?array
    {
        if ('' === $password) {
            return $this->getFalseResult(
                $this->trans('password.empty', [], 'validators'),
            );
        }

        return null;
    }

    private function validateScoreLevel(StrengthLevel $minimumLevel, StrengthLevel $scoreLevel, array $results): ?array
    {
        if ($scoreLevel->isSmaller($minimumLevel)) {
            $message = $this->translateScore($minimumLevel, $scoreLevel);

            return $this->getFalseResult($message, $results);
        }

        return null;
    }

    /**
     * @param array{score: int, warning?: string, suggestions?: array} $results
     */
    private function validateScoreResults(array $results): ?array
    {
        $score = $results['score'];
        if (!StrengthLevel::tryFrom($score) instanceof StrengthLevel) {
            $message = $this->translateInvalidLevel($score);

            return $this->getFalseResult($message, $results);
        }

        return null;
    }

    private function validateStrength(int $strength, array $results): ?array
    {
        if (!StrengthLevel::tryFrom($strength) instanceof StrengthLevel) {
            $message = $this->translateInvalidLevel($strength);

            return $this->getFalseResult($message, \array_merge(['minimum' => $strength], $results));
        }

        return null;
    }
}
