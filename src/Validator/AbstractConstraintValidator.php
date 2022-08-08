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

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Abstract constraint validator.
 *
 * @template T of Constraint
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
     * @throws UnexpectedTypeException if the given class name is not a subclass of the Constraint class
     */
    public function __construct(string $className)
    {
        if (!\is_subclass_of($className, Constraint::class)) {
            throw new UnexpectedTypeException($className, Constraint::class);
        }

        $this->className = $className;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!\is_a($constraint, $this->className)) {
            throw new UnexpectedTypeException($constraint, $this->className);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_scalar($value) && !$value instanceof \Stringable && !(\is_object($value) && \method_exists($value, '__toString'))) {
            throw new UnexpectedValueException($value, 'string');
        }

        $value = (string) $value;
        if ('' === $value) {
            return;
        }

        $this->doValidate($value, $constraint);
    }

    /**
     * Performs validation.
     *
     * @param string $value      the value that should be validated
     * @param T      $constraint
     */
    abstract protected function doValidate(string $value, Constraint $constraint): void;
}
