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
use App\Model\PasswordResult;
use App\Traits\StrengthLevelTranslatorTrait;
use App\Utils\StringUtils;
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

    public function __construct(
        private readonly ZxcvbnFactoryInterface $factory,
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * Gets the score (strength level) for the given query.
     */
    public function getScore(PasswordQuery $query): StrengthLevel
    {
        $result = $this->getPasswordStrength($query);

        return $result->getStrengthLevel() ?? StrengthLevel::NONE;
    }

    #[\Override]
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
        $response = $this->validateQuery($query);
        if (null !== $response) {
            return $response;
        }

        $result = $this->getPasswordStrength($query);
        $response = $this->validateResult($query, $result);
        if (null !== $response) {
            return $response;
        }

        $queryLevel = $query->strength;
        $resultLevel = $result->getStrengthLevel();

        return \array_merge($result->toArray(), [
            'result' => true,
            'minimum' => [
                'value' => $queryLevel->value,
                'percent' => $queryLevel->percent(),
                'text' => $this->translateLevel($queryLevel),
            ],
            'score' => [
                'value' => $resultLevel->value,
                'percent' => $resultLevel->percent(),
                'text' => $this->translateLevel($resultLevel),
            ],
        ]);
    }

    private function getFalseResult(string $message, ?PasswordResult $result = null): array
    {
        return [
            'result' => false,
            'message' => $message,
        ] + ($result?->toArray() ?? []);
    }

    private function getPasswordStrength(PasswordQuery $query): PasswordResult
    {
        /** @phpstan-var array{score: int, feedback: array{warning: string, suggestions: string[]}} $result */
        $result = $this->getService()->passwordStrength($query->password, $query->getInputs());

        return new PasswordResult(
            $result['score'],
            StringUtils::trim($result['feedback']['warning']),
            $this->trimArray($result['feedback']['suggestions'])
        );
    }

    private function getService(): Zxcvbn
    {
        return $this->service ??= $this->factory->createZxcvbn();
    }

    private function trimArray(array $array): ?array
    {
        return [] === $array ? null : $array;
    }

    private function validateQuery(PasswordQuery $query): ?array
    {
        if (null === StringUtils::trim($query->password)) {
            return $this->getFalseResult($this->trans('password.empty', domain: 'validators'));
        }
        if (StrengthLevel::NONE === $query->strength) {
            return $this->getFalseResult($this->trans('password.strength_disabled', domain: 'validators'));
        }

        return null;
    }

    /**
     * @phpstan-assert StrengthLevel $result->getStrengthLevel()
     */
    private function validateResult(PasswordQuery $query, PasswordResult $result): ?array
    {
        $score = $result->getStrengthLevel();
        if (!$score instanceof StrengthLevel) {
            return $this->getFalseResult($this->translateInvalidLevel($result->score), $result);
        }
        if ($score->isSmaller($query->strength)) {
            return $this->getFalseResult($this->translateScore($query->strength, $score), $result);
        }

        return null;
    }
}
