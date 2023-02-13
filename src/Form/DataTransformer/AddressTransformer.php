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
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Mime\Address;

/**
 * Transforms between an Address string and an Address object.
 *
 * @implements DataTransformerInterface<Address, string>
 */
class AddressTransformer implements DataTransformerInterface
{
    /**
     * Transforms an Address object into a string.
     *
     * @psalm-param mixed $value
     *
     * @psalm-return Address|null
     */
    public function reverseTransform(mixed $value): ?Address
    {
        if (null === $value) {
            return null;
        }

        if (!\is_string($value)) {
            $message = \sprintf('A "string" expected, a "%s" given.', \get_debug_type($value));
            throw new TransformationFailedException($message);
        }

        try {
            $address = Address::create($value);
        } catch (\InvalidArgumentException $e) {
            $message = \sprintf('Unable to parse the address for the value "%s".', $value);
            throw new TransformationFailedException($message, $e->getCode(), $e);
        }

        return $address;
    }

    /**
     * Transforms an Address string into an Address object.
     *
     * @psalm-param mixed $value
     *
     * @psalm-return string|null
     */
    public function transform(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof Address) {
            $message = \sprintf('An Address expected, a "%s" given.', \get_debug_type($value));
            throw new TransformationFailedException($message);
        }

        return \htmlentities(\str_replace('"', '', $value->toString()));
    }
}
