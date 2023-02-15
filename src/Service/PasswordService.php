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
     * @param ?string $password the password to validate
     * @param int     $strength the minimum level
     * @param ?string $email    the optional user's email
     * @param ?string $user     the optional user's name
     *
     * @return array the validation result where the 'result' is boolean indicate, when true; the success
     */
    public function validate(?string $password, int $strength, ?string $email = null, ?string $user = null): array
    {
        // validate password
        if (null === $password || '' === $password) {
            return $this->getFalseResult(
                $this->trans('password.empty', [], 'validators'),
            );
        }

        // get results
        $results = $this->getPasswordStrength($password, \array_filter([$email, $user]));

        // validate score
        $score = $results['score'];
        if (null === $actualLevel = StrengthLevel::tryFrom($score)) {
            $message = $this->translateInvalidLevel($score);

            return $this->getFalseResult($message, $results);
        }
        $results = \array_merge($results, [
            'score' => [
                'value' => $score,
                'percent' => $actualLevel->percent(),
                'text' => $this->translateLevel($actualLevel),
            ],
        ]);

        // validate minimum level
        if (null === $minimumLevel = StrengthLevel::tryFrom($strength)) {
            $message = $this->translateInvalidLevel($strength);

            return $this->getFalseResult($message, ['minimum' => $strength] + $results);
        }
        $results = \array_merge($results, [
            'minimum' => [
                'value' => $minimumLevel->value,
                'percent' => $minimumLevel->percent(),
                'text' => $this->translateLevel($minimumLevel),
            ],
        ]);

        // none?
        if (StrengthLevel::NONE === $minimumLevel) {
            $message = $this->trans('password.strength_disabled', [], 'validators');

            return $this->getFalseResult($message, $results);
        }

        // valid?
        if ($actualLevel->isSmaller($minimumLevel)) {
            $message = $this->translateScore($minimumLevel, $actualLevel);

            return $this->getFalseResult($message, $results);
        }

        // ok
        return ['result' => true] + $results;
    }

    private function getFalseResult(string $message, array $values = []): array
    {
        return \array_merge(['result' => false, 'message' => $message], $values);
    }

    /**
     * @return array{score: int, warning?: string, suggestions?: array}
     */
    private function getPasswordStrength(string $password, array $userInputs): array
    {
        /** @psalm-var array{score: int, feedback: array{suggestions: array, warning: string}} $result */
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
        if (null === $this->service) {
            $this->service = $this->factory->createZxcvbn();
        }

        return $this->service;
    }
}
