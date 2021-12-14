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

namespace App\Validator;

use App\Interfaces\StrengthInterface;
use App\Traits\StrengthTranslatorTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZxcvbnPhp\Zxcvbn;

/**
 * Strength constraint validator.
 *
 * @extends AbstractConstraintValidator<Strength>
 *
 * @author Laurent Muller
 */
class StrengthValidator extends AbstractConstraintValidator
{
    use StrengthTranslatorTrait;

    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct(Strength::class);
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     *
     * @param Strength $constraint
     */
    protected function doValidate(string $value, Constraint $constraint): void
    {
        if ($constraint->minstrength > StrengthInterface::LEVEL_NONE) {
            $zx = new Zxcvbn();
            $strength = $zx->passwordStrength($value);
            $score = $strength['score'];
            if ($score < $constraint->minstrength) {
                $strength_min = $this->translateLevel($constraint->minstrength);
                $strength_current = $this->translateLevel($score);
                $parameters = [
                    '{{strength_min}}' => $strength_min,
                    '{{strength_current}}' => $strength_current,
                ];

                $this->context->buildViolation($constraint->minstrengthMessage)
                    ->setParameters($parameters)
                    ->setInvalidValue($value)
                    ->addViolation();
            }
        }
    }
}
