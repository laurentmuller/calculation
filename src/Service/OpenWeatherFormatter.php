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

use App\Utils\FormatUtils;
use Symfony\Component\Intl\Countries;

/**
 * Service to format and update openweather results.
 */
class OpenWeatherFormatter
{
    // date formats
    private const DATE_FORMATS = [
        'date' => [\IntlDateFormatter::SHORT, \IntlDateFormatter::NONE],
        'time' => [\IntlDateFormatter::NONE, \IntlDateFormatter::SHORT],
        'date_time' => [\IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT],
        'date_time_medium' => [\IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT],
        'date_time_long' => [\IntlDateFormatter::LONG, \IntlDateFormatter::SHORT],
    ];

    // the country flag URL
    private const URL_COUNTRY = 'https://openweathermap.org/images/flags/%s.png';

    // the big icon URL
    private const URL_ICON_BIG = 'https://openweathermap.org/img/wn/%s@4x.png';

    // the small icon URL
    private const URL_ICON_SMALL = 'https://openweathermap.org/img/wn/%s@2x.png';

    public function __construct(private readonly PositionService $service)
    {
    }

    /**
     * Update the given results.
     */
    public function update(array &$results, ?\DateTimeZone $timezone = null): void
    {
        /** @phpstan-var mixed $result */
        foreach ($results as $key => &$result) {
            if (\is_array($result)) {
                $this->update($result, $timezone);
                $this->updateCoordinate($result);
            }

            match ($key) {
                'icon' => $this->updateIcon($results, (string) $result),
                'description' => $this->updateDescription($result),
                'country' => $this->updateCountry($results, (string) $results[$key]),
                'dt' => $this->updateDate($results, (int) $result, $timezone),
                'sunrise' => $this->updateSunrise($results, (int) $result, $timezone),
                'sunset' => $this->updateSunset($results, (int) $result, $timezone),
                'weather' => $this->updateWeather($result),
                'lat','latitude' => $this->updateLatitude($results, (float) $result),
                'lon','longitude' => $this->updateLongitude($results, (float) $result),
                'deg' => $this->updateDegree($results, (int) $result),
                'timezone' => $this->updateTimezone($results, $timezone),
                default => null,
            };
        }
    }

    /**
     * @phpstan-param array<int<-1,3>> $types
     */
    private function formatDate(int $date, array $types, ?\DateTimeZone $timezone = null): string
    {
        return FormatUtils::formatDateTime($date, $types[0], $types[1], timezone: $timezone);
    }

    private function getLatitude(array $result): ?float
    {
        /** @phpstan-var float|null */
        return $result['lat'] ?? $result['latitude'] ?? null;
    }

    private function getLongitude(array $result): ?float
    {
        /** @phpstan-var float|null */
        return $result['lon'] ?? $result['longitude'] ?? null;
    }

    /**
     * Update the latitude and longitude.
     */
    private function updateCoordinate(array &$result): void
    {
        $lat = $this->getLatitude($result);
        $lon = $this->getLongitude($result);
        if (null === $lat || null === $lon) {
            return;
        }
        $result['lat_lon_dms'] = $this->service->formatPosition($lat, $lon);
        $result['lat_lon_url'] = $this->service->getGoogleMapUrl($lat, $lon);
    }

    private function updateCountry(array &$result, string $country): void
    {
        if (Countries::exists($country)) {
            $result['country_name'] = Countries::getName($country);
            $result['country_flag'] = $this->updateUrl(self::URL_COUNTRY, \strtolower($country));
        }
    }

    private function updateDate(array &$result, int $date, ?\DateTimeZone $timezone = null): void
    {
        foreach (self::DATE_FORMATS as $key => $types) {
            $result['dt_' . $key] = $this->formatDate($date, $types);
            $result['dt_' . $key . '_locale'] = $this->formatDate($date, $types, $timezone);
        }
        unset($result['dt_txt']);
    }

    private function updateDegree(array &$result, int $value): void
    {
        $result['deg_direction'] = $this->service->getDirection($value);
        $result['deg_description'] = $this->service->formatDirection($value);
    }

    private function updateDescription(mixed &$result): void
    {
        if (\is_string($result) && '' !== $result) {
            $result = \ucfirst($result);
        }
    }

    private function updateIcon(array &$result, string $icon): void
    {
        $result['icon_big'] = $this->updateUrl(self::URL_ICON_BIG, $icon);
        $result['icon_small'] = $this->updateUrl(self::URL_ICON_SMALL, $icon);
    }

    private function updateLatitude(array &$result, float $latitude): void
    {
        $result['lat_dms'] = $this->service->formatLatitude($latitude);
    }

    private function updateLongitude(array &$result, float $longitude): void
    {
        $result['lon_dms'] = $this->service->formatLongitude($longitude);
    }

    private function updateSunrise(array &$result, int $date, ?\DateTimeZone $timezone = null): void
    {
        $result['sunrise_formatted'] = $this->formatDate($date, self::DATE_FORMATS['time'], $timezone);
    }

    private function updateSunset(array &$result, int $date, ?\DateTimeZone $timezone = null): void
    {
        $result['sunset_formatted'] = $this->formatDate($date, self::DATE_FORMATS['time'], $timezone);
    }

    private function updateTimezone(array &$result, ?\DateTimeZone $timezone = null): void
    {
        if ($timezone instanceof \DateTimeZone) {
            $result['timezone_name'] = $timezone->getName();
        }
    }

    private function updateUrl(string $url, string $value): string
    {
        return \sprintf($url, $value);
    }

    private function updateWeather(mixed &$value): void
    {
        if (\is_array($value)) {
            $value = (array) \reset($value);
        }
    }
}
