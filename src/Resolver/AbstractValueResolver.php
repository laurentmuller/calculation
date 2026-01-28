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

namespace App\Resolver;

use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract readonly class AbstractValueResolver implements ValueResolverInterface
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    /**
     * Validates the given value.
     *
     * @throws UnprocessableEntityHttpException if the object is not valid
     */
    protected function validate(object $value): void
    {
        $violations = $this->validator->validate($value);
        if (0 === \count($violations)) {
            return;
        }
        $message = $this->mapErrors($value, $violations);
        $previous = new ValidationFailedException($value, $violations);
        throw new UnprocessableEntityHttpException($message, $previous);
    }

    private function formatError(string $class, ConstraintViolationInterface $violation): string
    {
        return \sprintf('%s.%s: %s', $class, $violation->getPropertyPath(), $violation->getMessage());
    }

    private function mapErrors(object $value, ConstraintViolationListInterface $errors): string
    {
        $class = $value::class;
        $messages = \array_map(
            fn (ConstraintViolationInterface $violation): string => $this->formatError($class, $violation),
            \iterator_to_array($errors)
        );

        return \implode("\n", $messages);
    }
}
