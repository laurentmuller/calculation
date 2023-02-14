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

    private readonly Zxcvbn $service;

    /**
     * Constructor.
     */
    public function __construct(ZxcvbnFactoryInterface $factory, private readonly TranslatorInterface $translator)
    {
        $this->service = $factory->createZxcvbn();
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Validate the given password.
     *
     * @param ?string $password the password to validate
     * @param int     $strength the minimum level
     * @param ?string $email    the optional user's email
     * @param ?string $user     the optional user's name
     *
     * @return array the validation result where the 'result' is boolean indicate, when true; the success
     */
    public function validate(?string $password, int $strength, ?string $email = null, ?string $user = null): array
    {
        // password?
        if (null === $password || '' === $password) {
            return $this->getFalseResult(
                $this->trans('password.empty', [], 'validators'),
            );
        }

        // get results
        $results = $this->getPasswordStrength($password, \array_filter([$email, $user]));

        $score = $results['score'];
        if (null === $actualLevel = StrengthLevel::tryFrom($score)) {
            $message = $this->translateInvalidLevel($score);

            return $this->getFalseResult($message, $results);
        }

        if (null === $minimumLevel = StrengthLevel::tryFrom($strength)) {
            $message = $this->translateInvalidLevel($strength);
            $results = \array_merge($results, ['minimum' => $strength]);

            return $this->getFalseResult($message, $results);
        }

        // default
        $results = \array_merge($results, [
            'percent' => 0,
            'minimum' => $minimumLevel->value,
            'minimumText' => $this->translateLevel($minimumLevel),
        ]);

        // none?
        if (StrengthLevel::NONE === $minimumLevel) {
            $message = $this->trans('password.strength_disabled', [], 'validators');

            return $this->getFalseResult($message, $results);
        }

        // update
        $results = \array_merge($results, [
            'percent' => ($actualLevel->value + 1) * 20,
            'scoreText' => $this->translateLevel($actualLevel),
        ]);

        // valid?
        if ($actualLevel->isSmaller($minimumLevel)) {
            $message = $this->translateScore($minimumLevel, $actualLevel);

            return $this->getFalseResult($message, $results);
        }

        // ok
        return \array_merge($results, ['result' => true]);
    }

    private function getFalseResult(string $message, array $values = []): array
    {
        return \array_merge($values, ['result' => false, 'message' => $message]);
    }

    /**
     * @return array{score: int, warning?: string, suggestions?: array}
     */
    private function getPasswordStrength(string $password, array $userInputs): array
    {
        /** @psalm-var array{score: int, feedback: array{suggestions: array, warning: string}} $result */
        $result = $this->service->passwordStrength($password, $userInputs);

        return \array_merge(
            ['score' => $result['score']],
            \array_filter([
                'warning' => $result['feedback']['warning'],
                'suggestions' => $result['feedback']['suggestions'],
            ]),
        );
    }
}
