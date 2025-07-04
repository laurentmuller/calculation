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

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\InvalidArgumentException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Exception\ExceptionInterface;

/**
 * Transforms between an Address string and an Address object.
 *
 * @implements DataTransformerInterface<Address, string>
 */
class AddressTransformer implements DataTransformerInterface
{
    /** @phpstan-param mixed $value */
    #[\Override]
    public function reverseTransform(mixed $value): ?Address
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        try {
            return Address::create($value);
        } catch (ExceptionInterface $e) {
            throw new InvalidArgumentException(\sprintf('Unable to parse the address for the value "%s".', $value), $e->getCode(), $e);
        }
    }

    /** @phpstan-param mixed $value */
    #[\Override]
    public function transform(mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!$value instanceof Address) {
            throw new UnexpectedTypeException($value, Address::class);
        }

        return \htmlentities(\str_replace('"', '', $value->toString()));
    }
}
