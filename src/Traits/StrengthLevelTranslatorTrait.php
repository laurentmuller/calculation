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

namespace App\Traits;

use App\Enums\StrengthLevel;
use App\Validator\Strength;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Trait to translate {@see StrengthLevel}.
 */
trait StrengthLevelTranslatorTrait
{
    use TranslatorTrait;

    /**
     * Add a violation.
     */
    public function addStrengthLevelViolation(
        ExecutionContextInterface $context,
        Strength $constraint,
        StrengthLevel $minimum,
        StrengthLevel $score
    ): void {
        $parameters = [
            '%minimum%' => $this->translateLevel($minimum),
            '%score%' => $this->translateLevel($score),
        ];
        $context->buildViolation($constraint->strength_message)
            ->setCode(Strength::IS_STRENGTH_ERROR)
            ->setParameters($parameters)
            ->addViolation();
    }

    /**
     * Translate an invalid strength value.
     */
    public function translateInvalidLevel(StrengthLevel|int $value): string
    {
        if ($value instanceof StrengthLevel) {
            $value = $value->value;
        }

        return $this->trans('password.strength_invalid', [
            '%allowed%' => \implode(', ', StrengthLevel::values()),
            '%value%' => $value,
        ], 'validators');
    }

    /**
     * Translate the strength level.
     */
    public function translateLevel(StrengthLevel $level): string
    {
        return $this->trans($level);
    }

    /**
     * Translate the score.
     */
    public function translateScore(StrengthLevel $minimum, StrengthLevel $score): string
    {
        return $this->trans('password.strength_level', [
            '%minimum%' => $this->translateLevel($minimum),
            '%score%' => $this->translateLevel($score),
        ], 'validators');
    }
}
