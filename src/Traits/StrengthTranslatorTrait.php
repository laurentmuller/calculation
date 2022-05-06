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

use App\Form\Type\MinStrengthType;
use App\Interfaces\StrengthInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Trait to deal with strength levels.
 */
trait StrengthTranslatorTrait
{
    use MathTrait;
    use TranslatorTrait;

    /**
     * Add a violation to this context.
     */
    public function addStrengthViolation(ExecutionContextInterface $context, int $minimum, int $score): void
    {
        $parameters = [
            '%minimum%' => $this->translateLevel($minimum),
            '%current%' => $this->translateLevel($score),
        ];

        $context->buildViolation('password.min_strength')
            ->setParameters($parameters)
            ->addViolation();
    }

    /**
     * Translate the password strength level.
     */
    public function translateLevel(int $level): string
    {
        $level = $this->validateIntRange($level, StrengthInterface::LEVEL_NONE, StrengthInterface::LEVEL_VERY_STRONG);
        $id = MinStrengthType::CHOICE_LEVELS[$level];

        return $this->trans($id);
    }

    public function translateStrength(int $minimum, int $score): string
    {
        return $this->trans('password.min_strength', [
            '%minimum%' => $this->translateLevel($minimum),
            '%score%' => $this->translateLevel($score),
        ], 'validators');
    }
}
