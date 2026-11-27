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

namespace App\Serializer;

use Symfony\Component\Clock\DatePoint;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class DatePointNormalizer implements DenormalizerInterface, NormalizerInterface
{
    /**
     * @param DateTimeNormalizer $dateTimeNormalizer
     */
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.datetime')]
        private NormalizerInterface&DenormalizerInterface $dateTimeNormalizer,
    ) {
    }

    /**
     * @phpstan-ignore method.childReturnType
     */
    #[\Override]
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): DatePoint
    {
        try {
            $dateTime = $this->dateTimeNormalizer->denormalize($data, \DateTimeImmutable::class, $format, $context);

            return DatePoint::createFromInterface($dateTime);
        } catch (\Throwable $e) {
            throw NotNormalizableValueException::createForUnexpectedDataType($e->getMessage(), $data, ['string'], $context['deserialization_path'] ?? null, true, $e->getCode(), $e);
        }
    }

    #[\Override]
    public function getSupportedTypes(?string $format): array
    {
        return [
            DatePoint::class => true,
        ];
    }

    #[\Override]
    public function normalize(mixed $data, ?string $format = null, array $context = []): int|float|string
    {
        return $this->dateTimeNormalizer->normalize($data, $format, $context);
    }

    #[\Override]
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return DatePoint::class === $type;
    }

    #[\Override]
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof DatePoint;
    }
}
