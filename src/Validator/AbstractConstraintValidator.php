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

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Abstract constraint validator.
 *
 * @template T of Constraint
 *
 * @author Laurent Muller
 */
abstract class AbstractConstraintValidator extends ConstraintValidator
{
    /**
     * The constraint class.
     *
     * @psalm-var class-string<T> $className
     */
    protected string $className;

    /**
     * Constructor.
     *
     * @param string $className the constraint class
     * @psalm-param class-string<T> $className
     *
     * @throws \InvalidArgumentException if the given class name is not a subclass of the Constraint class
     */
    public function __construct(string $className)
    {
        if (!\is_subclass_of($className, Constraint::class)) {
            throw new \InvalidArgumentException(\sprintf('Expected argument of type "%s", "%s" given', Constraint::class, $className));
        }

        $this->className = $className;
    }

    /**
     * {@inheritdoc}
     *
     * @psalm-param T $constraint
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!\is_a($constraint, $this->className)) {
            throw new UnexpectedTypeException($constraint, $this->className);
        }

        if ($this->isAllowEmpty() && (null === $value || '' === $value)) {
            return;
        }

        $value = $this->convert($value);
        if ($this->isAllowEmpty() && '' === $value) {
            return;
        }

        $this->doValidate($value, $constraint);
    }

    /**
     * Checks and converts the given value.
     *
     * @param mixed $value the value to checks
     *
     * @throws UnexpectedValueException if the value can not be converted
     *
     * @return mixed the converted value
     */
    protected function convert($value)
    {
        if (!\is_scalar($value) && !(\is_object($value) && \method_exists($value, '__toString'))) {
            throw new UnexpectedValueException($value, 'string');
        }

        return (string) $value;
    }

    /**
     * Performs validation.
     *
     * @param mixed $value the value that should be validated
     * @psalm-param T $constraint
     */
    abstract protected function doValidate($value, Constraint $constraint): void;

    /**
     * Returns a value indicating if the value to be tested can be null or empty.
     *
     * If true and the value to validate is null or empty, no validation is performed.
     */
    protected function isAllowEmpty(): bool
    {
        return true;
    }
}
