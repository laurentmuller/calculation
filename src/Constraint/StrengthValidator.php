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
use App\Model\PasswordQuery;
use App\Service\PasswordService;
use App\Utils\StringUtils;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * Strength constraint validator.
 *
 * @extends AbstractConstraintValidator<Strength>
 */
class StrengthValidator extends AbstractConstraintValidator
{
    private readonly PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        private readonly PasswordService $service,
        ?PropertyAccessorInterface $propertyAccessor = null
    ) {
        parent::__construct(Strength::class);
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
    }

    #[\Override]
    public function validate(#[\SensitiveParameter] mixed $value, Constraint $constraint): void
    {
        parent::validate($value, $constraint);
    }

    /**
     * @param Strength $constraint
     */
    #[\Override]
    protected function doValidate(#[\SensitiveParameter] string $value, Constraint $constraint): void
    {
        $minimum = $constraint->minimum;
        if (StrengthLevel::NONE === $minimum) {
            return;
        }

        $query = $this->getQuery($value, $constraint);
        $score = $this->service->getScore($query);
        if ($score->isSmaller($minimum)) {
            $this->addViolation($constraint, $score);
        }
    }

    private function addViolation(Strength $constraint, StrengthLevel $score): void
    {
        $parameters = [
            '%minimum%' => $this->service->translateLevel($constraint->minimum),
            '%score%' => $this->service->translateLevel($score),
        ];
        $this->context->buildViolation($constraint->strength_message)
            ->setCode(Strength::STRENGTH_ERROR)
            ->setParameters($parameters)
            ->addViolation();
    }

    private function getQuery(string $password, Strength $constraint): PasswordQuery
    {
        return new PasswordQuery(
            $password,
            $constraint->minimum,
            $this->getValue($constraint->userNamePath),
            $this->getValue($constraint->emailPath),
        );
    }

    private function getValue(?string $path): ?string
    {
        if (null === $path) {
            return null;
        }

        /** @phpstan-var object $object */
        $object = $this->context->getObject();

        try {
            return (string) $this->propertyAccessor->getValue($object, $path);
        } catch (NoSuchPropertyException $e) {
            throw new ConstraintDefinitionException(\sprintf('Invalid property path "%s" for "%s".', $path, StringUtils::getDebugType($object)), $e->getCode(), $e);
        }
    }
}
