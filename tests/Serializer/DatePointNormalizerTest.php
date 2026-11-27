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

namespace App\Tests\Serializer;

use App\Serializer\DatePointNormalizer;
use App\Utils\DateUtils;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;

final class DatePointNormalizerTest extends TestCase
{
    private DatePointNormalizer $normalizer;

    #[\Override]
    protected function setUp(): void
    {
        $context = [DateTimeNormalizer::FORMAT_KEY => DateUtils::FORM_DATE_FORMAT];
        $dateTimeNormalizer = new DateTimeNormalizer($context);
        $this->normalizer = new DatePointNormalizer($dateTimeNormalizer);
    }

    public function testDenormalize(): void
    {
        $date = new DatePoint('2026-01-15 12:45:55');
        $value = $date->format('Y-m-d H:i:s');
        $actual = $this->normalizer->denormalize($value, DatePoint::class);
        self::assertSame($actual->format('Y-m-d H:i:s'), $date->format('Y-m-d H:i:s'));
    }

    public function testDenormalizeInvalid(): void
    {
        self::expectException(NotNormalizableValueException::class);
        $this->normalizer->denormalize(new \stdClass(), DatePoint::class);
    }

    public function testGetSupportedTypes(): void
    {
        self::assertSame([DatePoint::class => true], $this->normalizer->getSupportedTypes('json'));
    }

    /**
     * @throws ExceptionInterface
     */
    public function testNormalize(): void
    {
        $datePoint = new DatePoint('2026-01-15 12:45:55');
        $result = $this->normalizer->normalize($datePoint);
        self::assertSame('2026-01-15', $result);
    }

    public function testSupportsDenormalization(): void
    {
        self::assertTrue($this->normalizer->supportsDenormalization(null, DatePoint::class));
        self::assertFalse($this->normalizer->supportsDenormalization(null, \stdClass::class));
    }

    public function testSupportsNormalization(): void
    {
        self::assertTrue($this->normalizer->supportsNormalization(new DatePoint()));
        self::assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }
}
