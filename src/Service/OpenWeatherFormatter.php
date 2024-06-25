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
use Symfony\Component\Intl\Exception\MissingResourceException;

/**
 * Service to format and update openweather results.
 */
class OpenWeatherFormatter
{
    /**
     * The country flag URL.
     */
    private const COUNTRY_URL = 'https://openweathermap.org/images/flags/{0}.png';

    /**
     * The big icon URL.
     */
    private const ICON_BIG_URL = 'https://openweathermap.org/img/wn/{0}@4x.png';

    /**
     * The small icon URL.
     */
    private const ICON_SMALL_URL = 'https://openweathermap.org/img/wn/{0}@2x.png';

    public function __construct(private readonly PositionService $service)
    {
    }

    /**
     * Update the given results.
     */
    public function update(array &$results, ?\DateTimeZone $timezone = null): void
    {
        /** @psalm-var mixed $result */
        foreach ($results as $key => &$result) {
            if (\is_array($result)) {
                $this->update($result, $timezone);
                $this->updateCoordinate($result);
            }

            switch ((string) $key) {
                case 'icon':
                    $this->updateIcon($results, (string) $result);
                    break;
                case 'description':
                    $this->updateDescription($result);
                    break;
                case 'country':
                    $this->updateCountry($results);
                    break;
                case 'dt':
                    $this->updateDate($results, (int) $result, $timezone);
                    break;
                case 'sunrise':
                    $this->updateSunrise($results, (int) $result, $timezone);
                    break;
                case 'sunset':
                    $this->updateSunset($results, (int) $result, $timezone);
                    break;
                case 'weather':
                    $this->updateWeather($result);
                    break;
                case 'lat':
                case 'latitude':
                    $this->updateLatitude($results, (float) $result);
                    break;
                case 'lon':
                case 'longitude':
                    $this->updateLongitude($results, (float) $result);
                    break;
                case 'deg':
                    $this->updateDegree($results, (int) $result);
                    break;
                case 'timezone':
                    $this->updateTimezone($results, $timezone);
                    break;
            }
        }
    }

    /**
     * Gets the country name from the alpha2 code.
     */
    private function getCountryName(string $country): string
    {
        try {
            return Countries::getName($country);
        } catch (MissingResourceException) {
            return '';
        }
    }

    private function getLatitude(array $result): ?float
    {
        /** @psalm-var float|null */
        return $result['lat'] ?? $result['latitude'] ?? null;
    }

    private function getLongitude(array $result): ?float
    {
        /** @psalm-var float|null */
        return $result['lon'] ?? $result['longitude'] ?? null;
    }

    private function replaceUrl(string $url, string $value): string
    {
        return \str_replace('{0}', $value, $url);
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

        $result['lat_dms'] = $this->service->formatLatitude($lat);
        $result['lon_dms'] = $this->service->formatLongitude($lon);
        $result['lat_lon_dms'] = $this->service->formatPosition($lat, $lon);
        $result['lat_lon_url'] = $this->service->getGoogleMapUrl($lat, $lon);
    }

    private function updateCountry(array &$result): void
    {
        /** @psalm-var string $country */
        $country = $result['country'];
        $result['country_name'] = $this->getCountryName($country);
        $result['country_flag'] = $this->replaceUrl(\strtolower($country), self::COUNTRY_URL);
    }

    private function updateDate(array &$result, int $date, ?\DateTimeZone $timezone = null): void
    {
        $result['dt_date'] = FormatUtils::formatDate($date, \IntlDateFormatter::SHORT);
        $result['dt_date_locale'] = FormatUtils::formatDate($date, \IntlDateFormatter::SHORT, timezone: $timezone);
        $result['dt_time'] = FormatUtils::formatTime($date, \IntlDateFormatter::SHORT);
        $result['dt_time_locale'] = FormatUtils::formatTime($date, \IntlDateFormatter::SHORT, timezone: $timezone);
        $result['dt_date_time'] = FormatUtils::formatDateTime($date, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT);
        $result['dt_date_time_locale'] = FormatUtils::formatDateTime($date, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT, timezone: $timezone);
        $result['dt_date_time_medium'] = FormatUtils::formatDateTime($date, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT);
        $result['dt_date_time_medium_locale'] = FormatUtils::formatDateTime($date, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::SHORT, timezone: $timezone);
        $result['dt_date_time_long'] = FormatUtils::formatDateTime($date, \IntlDateFormatter::LONG, \IntlDateFormatter::SHORT);
        $result['dt_date_time_long_locale'] = FormatUtils::formatDateTime($date, \IntlDateFormatter::LONG, \IntlDateFormatter::SHORT, timezone: $timezone);
        unset($result['dt_txt']);
    }

    private function updateDegree(array &$result, int $value): void
    {
        $result['deg_direction'] = $this->service->getDirection($value);
        $result['deg_description'] = $this->service->formatDirection($value);
    }

    private function updateDescription(mixed &$result): void
    {
        if (\is_string($result)) {
            $result = \ucfirst($result);
        }
    }

    private function updateIcon(array &$result, string $icon): void
    {
        $result['icon_big'] = $this->replaceUrl(self::ICON_BIG_URL, $icon);
        $result['icon_small'] = $this->replaceUrl(self::ICON_SMALL_URL, $icon);
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
        $result['sunrise_formatted'] = FormatUtils::formatTime($date, \IntlDateFormatter::SHORT, timezone: $timezone);
    }

    private function updateSunset(array &$result, int $date, ?\DateTimeZone $timezone = null): void
    {
        $result['sunset_formatted'] = FormatUtils::formatTime($date, \IntlDateFormatter::SHORT, timezone: $timezone);
    }

    private function updateTimezone(array &$result, ?\DateTimeZone $timezone = null): void
    {
        if ($timezone instanceof \DateTimeZone) {
            $result['timezone_name'] = $timezone->getName();
        }
    }

    private function updateWeather(mixed &$value): void
    {
        if (\is_array($value)) {
            $value = (array) \reset($value);
        }
    }
}
