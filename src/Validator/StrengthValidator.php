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

use App\Traits\MathTrait;
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

    private TranslatorInterface $translator;

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
     *
     * @param Strength $constraint
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
