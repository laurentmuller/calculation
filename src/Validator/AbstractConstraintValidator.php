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

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Abstract constraint validator.
 *
 * @author Laurent Muller
 */
abstract class AbstractConstraintValidator extends ConstraintValidator
{
    /**
     * The constraint class.
     *
     * @var string
     */
    protected $class;

    /**
     * Constructor.
     *
     * @param string $class the constraint class
     */
    public function __construct(string $class)
    {
        $this->class = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (\get_class($constraint) !== $this->class) {
            throw new UnexpectedTypeException($constraint, $this->class);
        }

        if ($this->isAllowEmpty() && (null === $value || '' === $value)) {
            return;
        }

        if ($this->toString()) {
            if (!\is_scalar($value) && !(\is_object($value) && \method_exists($value, '__toString'))) {
                throw new UnexpectedValueException($value, 'string');
            }
            $this->doValidate((string) $value, $constraint);
        } else {
            $this->doValidate($value, $constraint);
        }
    }

    /**
     * Performs validation.
     *
     * @param mixed      $value      the value that should be validated
     * @param Constraint $constraint the constraint
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

    /**
     * Returns a value indicating if the value must converted to a string before validation.
     *
     * @return true to convert to a string, false to perform the validation with the value "as is"
     */
    protected function toString(): bool
    {
        return true;
    }
}
