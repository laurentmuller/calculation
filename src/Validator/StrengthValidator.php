<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Validator;

use App\Traits\MathTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZxcvbnPhp\Zxcvbn;

/**
 * Strength constraint validator.
 *
 * @author Laurent Muller
 */
class StrengthValidator extends AbstractConstraintValidator
{
    use MathTrait;

    /**
     * The strength levels.
     */
    private const LEVEL_TO_LABEL = [
        0 => 'very_weak',
        1 => 'weak',
        2 => 'medium',
        3 => 'strong',
        4 => 'very_strong',
    ];

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
        parent::__construct(Strength::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function doValidate($value, Constraint $constraint): void
    {
        $minStrength = $this->validateIntRange($constraint->minStrength, Strength::LEVEL_DISABLE, Strength::LEVEL_MAX);
        if (Strength::LEVEL_DISABLE === $minStrength) {
            return;
        }

        $zx = new Zxcvbn();
        $strength = $zx->passwordStrength($value);
        $score = $strength['score'];
        if ($score < $minStrength) {
            $strength_min = $this->translateLevel($minStrength);
            $strength_current = $this->translateLevel($score);
            $parameters = [
                    '{{strength_min}}' => $strength_min,
                    '{{strength_current}}' => $strength_current,
                ];

            $this->context->buildViolation('password.minStrength')
                ->setParameters($parameters)
                ->setInvalidValue($value)
                ->addViolation();
        }
    }

    /**
     * Translate the level.
     *
     * @param int $level the level (0 - 4)
     *
     * @return string the translated level
     */
    private function translateLevel(int $level): string
    {
        $level = $this->validateIntRange($level, Strength::LEVEL_MIN, Strength::LEVEL_MAX);
        $id = 'password.strength_level.' . self::LEVEL_TO_LABEL[$level];

        return $this->translator->trans($id);
    }
}
