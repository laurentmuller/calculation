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

namespace App\Service;

use App\Traits\TranslatorTrait;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to format direction, latitude, and longitude values.
 */
class PositionService
{
    use TranslatorTrait;

    public const float MAX_LATITUDE = 90.0;
    public const float MAX_LONGITUDE = 180.0;
    public const float MIN_LATITUDE = -90.0;
    public const float MIN_LONGITUDE = -180.0;

    /** The directions. */
    private const array DIRECTIONS = [
        'N',
        'N / N-E',
        'N-E',
        'E / N-E',
        'E',
        'E / S-E',
        'S-E',
        'S / S-E',
        'S',
        'S / S-W',
        'S-W',
        'W / S-W',
        'W',
        'W / N-W',
        'N-W',
        'N / N-W',
        'N',
    ];

    private const string GOOGLE_MAP_URL = 'https://www.google.ch/maps/place/%s,%s';

    /** The search terms. */
    private const array SEARCH = [
        'N',
        'S',
        'E',
        'W',
    ];

    /**
     * The replacement terms.
     *
     * @var string[]|null
     */
    private ?array $replace = null;

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Format the given direction.
     */
    public function formatDirection(float $deg): string
    {
        $direction = $this->getDirection($deg);

        return \str_replace(self::SEARCH, $this->getReplace(), $direction);
    }

    /**
     * Format the given latitude to degrees, minutes, and seconds.
     *
     * @throws \InvalidArgumentException if the latitude is not between -90 to +90 (inclusive)
     */
    public function formatLatitude(float $latitude): string
    {
        $this->checkLatitude($latitude);

        return $this->formatValue($latitude, 'N', 'S');
    }

    /**
     * Format the given longitude to degrees, minutes, and seconds.
     *
     * @throws \InvalidArgumentException if the longitude is not between -180 to +180 (inclusive)
     */
    public function formatLongitude(float $longitude): string
    {
        $this->checkLongitude($longitude);

        return $this->formatValue($longitude, 'E', 'W');
    }

    /**
     * Format the given latitude and longitude to degrees, minutes, and seconds.
     *
     * @throws \InvalidArgumentException if the latitude is not between -90 to +90 (inclusive) or
     *                                   if the longitude is not between -180 to +180 (inclusive)
     */
    public function formatPosition(float $latitude, float $longitude): string
    {
        return \sprintf('%s, %s', $this->formatLatitude($latitude), $this->formatLongitude($longitude));
    }

    /**
     * Get the direction for the given degrees.
     */
    public function getDirection(float $deg): string
    {
        $value = (int) $deg % 360;
        $index = (int) \floor((float) $value / 22.5 + 0.5);

        return self::DIRECTIONS[$index];
    }

    /**
     * Gets the Google Maps URL for the given latitude and longitude.
     *
     * @throws \InvalidArgumentException if the latitude is not between -90 to +90 (inclusive) or
     *                                   if the longitude is not between -180 to +180 (inclusive)
     */
    public function getGoogleMapUrl(float $latitude, float $longitude): string
    {
        $this->checkLatitude($latitude);
        $this->checkLongitude($longitude);

        return \sprintf(self::GOOGLE_MAP_URL, $latitude, $longitude);
    }

    #[\Override]
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function checkLatitude(float $latitude): void
    {
        if ($latitude < self::MIN_LATITUDE || $latitude > self::MAX_LATITUDE) {
            throw new \InvalidArgumentException(\sprintf('The latitude is not between %+d to %+d (inclusive). %.5f given.', self::MIN_LATITUDE, self::MAX_LATITUDE, $latitude));
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function checkLongitude(float $longitude): void
    {
        if ($longitude < self::MIN_LONGITUDE || $longitude > self::MAX_LONGITUDE) {
            throw new \InvalidArgumentException(\sprintf('The longitude is not between %+d to %+d (inclusive). %.5f given.', self::MIN_LONGITUDE, self::MAX_LONGITUDE, $longitude));
        }
    }

    private function formatValue(float $value, string $positiveSuffix, string $negativeSuffix): string
    {
        $suffix = $value >= 0 ? $positiveSuffix : $negativeSuffix;
        $suffix = $this->trans('openweather.direction.' . $suffix);
        $value = \abs($value);
        $degrees = \floor($value);
        $value = ($value - $degrees) * 60.0;
        $minutes = \floor($value);
        $seconds = \floor(($value - $minutes) * 60.0);

        return \sprintf("%d° %d' %d\" %s", $degrees, $minutes, $seconds, $suffix);
    }

    /**
     * @return string[]
     */
    private function getReplace(): array
    {
        return $this->replace ??= \array_map(
            fn (string $s): string => $this->trans('openweather.direction.' . $s),
            self::SEARCH
        );
    }
}
