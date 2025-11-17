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

use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Transforms between a DatePoint object and a DateTime object.
 *
 * @implements DataTransformerInterface<DatePoint, \DateTime>
 */
class DatePointToDateTimeTransformer implements DataTransformerInterface
{
    /**
     * @phpstan-param mixed $value
     */
    #[\Override]
    public function reverseTransform(mixed $value): ?DatePoint
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof \DateTime) {
            throw new UnexpectedTypeException($value, \DateTime::class);
        }

        return DatePoint::createFromMutable($value);
    }

    /**
     * @phpstan-param mixed $value
     */
    #[\Override]
    public function transform(mixed $value): ?\DateTime
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof DatePoint) {
            throw new UnexpectedTypeException($value, DatePoint::class);
        }

        return \DateTime::createFromImmutable($value);
    }
}
