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
 * Service to format latitude and longitude values.
 */
class PositionService
{
    use TranslatorTrait;

    /**
     * The directions.
     */
    private const DIRECTIONS = [
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

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * Translate the given direction.
     */
    public function formatDirection(float $deg): string
    {
        $direction = $this->getDirection($deg);
        $search = [
            'N',
            'S',
            'E',
            'W',
        ];
        $replace = [
            $this->trans('openweather.direction.N'),
            $this->trans('openweather.direction.S'),
            $this->trans('openweather.direction.E'),
            $this->trans('openweather.direction.W'),
        ];

        return \str_replace($search, $replace, $direction);
    }

    /**
     * Convert the given latitude to degrees, minutes and seconds.
     *
     * @throws \InvalidArgumentException if the latitude is not between -90 to +90 (inclusive)
     */
    public function formatLat(float $latitude): string
    {
        if ($latitude < -90 || $latitude > 90) {
            throw new \InvalidArgumentException(\sprintf('The latitude is not between -90 to +90 (inclusive). %.5f given.', $latitude));
        }

        return $this->formatPosition($latitude, 'N', 'S');
    }

    /**
     * Convert the given latitude and longitude to degrees, minutes and seconds.
     *
     * @throws \InvalidArgumentException if the latitude is not between -90 to +90 (inclusive) or
     *                                   if the longitude is not between -180 to +180 (inclusive)
     */
    public function formatLatLng(float $latitude, float $longitude): string
    {
        return \sprintf('%s / %s', $this->formatLat($latitude), $this->formatLng($longitude));
    }

    /**
     * Convert the given longitude to degrees, minutes and seconds.
     *
     * @throws \InvalidArgumentException if the longitude is not between -180 to +180 (inclusive)
     */
    public function formatLng(float $longitude): string
    {
        if ($longitude < -180 || $longitude > 180) {
            throw new \InvalidArgumentException(\sprintf('The longitude is not between -180 to +180 (inclusive). %.5f given.', $longitude));
        }

        return $this->formatPosition($longitude, 'E', 'W');
    }

    /**
     * Gets the direction for the given degrees.
     */
    public function getDirection(float $deg): string
    {
        $value = (int) $deg % 360;
        $index = (int) \floor((float) $value / 22.5 + 0.5);

        return self::DIRECTIONS[$index];
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    private function formatPosition(float $position, string $positiveSuffix, string $negativeSuffix): string
    {
        $suffix = $position >= 0 ? $positiveSuffix : $negativeSuffix;
        $suffix = $this->trans("openweather.direction.$suffix");
        $position = \abs($position);
        $degrees = \floor($position);
        $position = ($position - $degrees) * 60.0;
        $minutes = \floor($position);
        $seconds = \floor(($position - $minutes) * 60.0);

        return \sprintf("%d° %d' %d\" %s", $degrees, $minutes, $seconds, $suffix);
    }
}
