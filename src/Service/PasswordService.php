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
use App\Model\PasswordQuery;
use App\Traits\StrengthLevelTranslatorTrait;
use App\Utils\StringUtils;
use Createnl\ZxcvbnBundle\ZxcvbnFactoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZxcvbnPhp\Zxcvbn;

/**
 * Service to validate a password with Zxcvbn.
 *
 * @psalm-type ScoreResultType = array{
 *     score: int,
 *     warning?: string,
 *     suggestions?: string[]}
 */
class PasswordService
{
    use StrengthLevelTranslatorTrait;

    private ?Zxcvbn $service = null;

    public function __construct(
        private readonly ZxcvbnFactoryInterface $factory,
        private readonly TranslatorInterface $translator
    ) {
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Validate the given password query.
     *
     * @return array the validation results where the 'result' key is boolean indicate, when true; the success
     */
    public function validate(PasswordQuery $query): array
    {
        $response = $this->validatePassword($query);
        if (null !== $response) {
            return $response;
        }

        $results = $this->getPasswordStrength($query);
        $response = $this->validateScoreResults($results);
        if (null !== $response) {
            return $response;
        }

        $minimumLevel = $query->strength;
        $scoreLevel = StrengthLevel::from($results['score']);
        $results = \array_merge($results, [
            'minimum' => [
                'value' => $minimumLevel->value,
                'percent' => $minimumLevel->percent(),
                'text' => $this->translateLevel($minimumLevel),
            ],
            'score' => [
                'value' => $scoreLevel->value,
                'percent' => $scoreLevel->percent(),
                'text' => $this->translateLevel($scoreLevel),
            ],
        ]);

        $response = $this->validateScoreLevel($minimumLevel, $scoreLevel, $results);
        if (null !== $response) {
            return $response;
        }

        return \array_merge(['result' => true], $results);
    }

    private function getFalseResult(string $message, array $values = []): array
    {
        return \array_merge(['result' => false, 'message' => $message], $values);
    }

    /**
     * @psalm-return ScoreResultType
     */
    private function getPasswordStrength(PasswordQuery $query): array
    {
        /** @psalm-var array{score: int, feedback: array{warning: string, suggestions: string[]}} $result */
        $result = $this->getService()->passwordStrength($query->password, $query->getInputs());

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

    private function validatePassword(PasswordQuery $query): ?array
    {
        if (null === StringUtils::trim($query->password)) {
            return $this->getFalseResult($this->trans('password.empty', [], 'validators'));
        }

        return null;
    }

    private function validateScoreLevel(StrengthLevel $minimumLevel, StrengthLevel $scoreLevel, array $results): ?array
    {
        if (StrengthLevel::NONE === $minimumLevel) {
            $message = $this->trans('password.strength_disabled', [], 'validators');

            return $this->getFalseResult($message, $results);
        }

        if ($scoreLevel->isSmaller($minimumLevel)) {
            $message = $this->translateScore($minimumLevel, $scoreLevel);

            return $this->getFalseResult($message, $results);
        }

        return null;
    }

    /**
     * @psalm-param ScoreResultType $results
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
}
