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

namespace App\Constraint;

use App\Enums\StrengthLevel;
use App\Traits\StrengthLevelTranslatorTrait;
use Createnl\ZxcvbnBundle\ZxcvbnFactoryInterface;
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
    use StrengthLevelTranslatorTrait;

    private readonly PropertyAccessorInterface $propertyAccessor;
    private ?Zxcvbn $service = null;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ZxcvbnFactoryInterface $factory,
        ?PropertyAccessorInterface $propertyAccessor = null
    ) {
        parent::__construct(Strength::class);
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    public function validate(#[\SensitiveParameter] mixed $value, Constraint $constraint): void
    {
        parent::validate($value, $constraint);
    }

    /**
     * @param Strength $constraint
     */
    protected function doValidate(#[\SensitiveParameter] string $value, Constraint $constraint): void
    {
        $minimum = $constraint->minimum;
        if (StrengthLevel::NONE === $minimum) {
            return;
        }

        $service = $this->getService();
        $userInputs = $this->getUserInputs($constraint);
        /** @psalm-var array{score: int<0, 4>} $result */
        $result = $service->passwordStrength($value, $userInputs);
        $score = StrengthLevel::tryFrom($result['score']) ?? StrengthLevel::NONE;
        if ($score->isSmaller($minimum)) {
            $this->addStrengthLevelViolation($this->context, $constraint, $minimum, $score);
        }
    }

    private function getService(): Zxcvbn
    {
        return $this->service ??= $this->factory->createZxcvbn();
    }

    private function getUserInputs(Strength $constraint): array
    {
        $userInputs = [];
        $object = $this->context->getObject();
        if (null === $object) {
            return $userInputs;
        }
        if (null !== $constraint->userNamePath) {
            $userInputs[] = $this->getValue($object, $constraint->userNamePath);
        }
        if (null !== $constraint->emailPath) {
            $userInputs[] = $this->getValue($object, $constraint->emailPath);
        }

        return \array_filter($userInputs);
    }

    private function getValue(object $object, string $path): string
    {
        try {
            return (string) $this->propertyAccessor->getValue($object, $path);
        } catch (NoSuchPropertyException $e) {
            throw new ConstraintDefinitionException(message: \sprintf('Invalid property path "%s" for "%s".', $path, \get_debug_type($object)), previous: $e);
        }
    }
}
