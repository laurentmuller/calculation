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

    /**
     * Constructor.
     */
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly ZxcvbnFactoryInterface $factory,
        ?PropertyAccessorInterface $propertyAccessor = null
    ) {
        parent::__construct(Strength::class);
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * {@inheritdoc}
     *
     * @param Strength $constraint
     */
    protected function doValidate(string $value, Constraint $constraint): void
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
        if (null === $this->service) {
            $this->service = $this->factory->createZxcvbn();
        }

        return $this->service;
    }

    private function getUserInputs(Strength $constraint): array
    {
        if (null === $object = $this->context->getObject()) {
            return [];
        }

        $userInputs = [];
        if (null !== $path = $constraint->userNamePath) {
            $userInputs[] = $this->getValue($object, $path);
        }
        if (null !== $path = $constraint->emailPath) {
            $userInputs[] = $this->getValue($object, $path);
        }

        return \array_filter($userInputs);
    }

    private function getValue(object $object, string $path): string
    {
        try {
            return (string) $this->propertyAccessor->getValue($object, $path);
        } catch (NoSuchPropertyException $e) {
            throw new ConstraintDefinitionException(\sprintf('Invalid property path "%s" provided for object "%s".', $path, \get_debug_type($object)), $e->getCode(), $e);
        }
    }
}
