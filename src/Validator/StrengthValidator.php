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

namespace App\Validator;

use App\Interfaces\StrengthInterface;
use App\Traits\StrengthTranslatorTrait;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Contracts\Translation\TranslatorInterface;
use ZxcvbnPhp\Zxcvbn;

/**
 * Strength constraint validator.
 *
 * @extends AbstractConstraintValidator<Strength>
 */
class StrengthValidator extends AbstractConstraintValidator
{
    use StrengthTranslatorTrait;

    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator, private ?PropertyAccessorInterface $propertyAccessor = null)
    {
        parent::__construct(Strength::class);
        $this->setTranslator($translator);
    }

    /**
     * {@inheritdoc}
     *
     * @param Strength $constraint
     */
    protected function doValidate(string $value, Constraint $constraint): void
    {
        $minstrength = $constraint->minstrength;
        if (StrengthInterface::LEVEL_NONE === $minstrength) {
            return;
        }

        $zx = new Zxcvbn();
        $userInputs = $this->getUserInputs($constraint);
        $strength = $zx->passwordStrength($value, $userInputs);
        $score = (int) $strength['score'];
        if ($score < $minstrength) {
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

    private function getPropertyAccessor(): PropertyAccessorInterface
    {
        if (null === $this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * @return string[]
     */
    private function getUserInputs(Strength $constraint): array
    {
        /** @var string[] $userInputs */
        $userInputs = [];
        if (null === $object = $this->context->getObject()) {
            return $userInputs;
        }

        if ($path = $constraint->userNamePath) {
            try {
                $userInputs[] = (string) $this->getPropertyAccessor()->getValue($object, $path);
            } catch (NoSuchPropertyException $e) {
                throw new ConstraintDefinitionException(\sprintf('Invalid property path "%s" provided to "%s" constraint: ', $path, \get_debug_type($constraint)) . $e->getMessage(), 0, $e);
            }
        }
        if ($path = $constraint->emailPath) {
            try {
                $userInputs[] = (string) $this->getPropertyAccessor()->getValue($object, $path);
            } catch (NoSuchPropertyException $e) {
                throw new ConstraintDefinitionException(\sprintf('Invalid property path "%s" provided to "%s" constraint: ', $path, \get_debug_type($constraint)) . $e->getMessage(), 0, $e);
            }
        }

        return $userInputs;
    }
}
