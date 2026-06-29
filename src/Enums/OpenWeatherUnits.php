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

namespace App\Enums;

use App\Interfaces\DefaultEnumInterface;
use App\Interfaces\EnumSortableInterface;
use App\Traits\EnumExtrasTrait;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\Attribute\ReadableEnum;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumInterface;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumTrait;

/**
 * OpenWeatherMap units enumeration.
 *
 * @implements DefaultEnumInterface<OpenWeatherUnits>
 * @implements EnumSortableInterface<OpenWeatherUnits>
 */
#[ReadableEnum(prefix: 'openweather.current.', suffix: '.text', useValueAsDefault: true)]
enum OpenWeatherUnits: string implements DefaultEnumInterface, EnumSortableInterface, TranslatableEnumInterface
{
    use EnumExtrasTrait;
    use TranslatableEnumTrait;

    /** Imperial unit. */
    #[EnumCase(extras: ['degree' => '°F', 'speed' => 'mph'])]
    case IMPERIAL = 'imperial';

    /** Metric unit. */
    #[EnumCase(extras: ['degree' => '°C', 'speed' => 'm/s'])]
    case METRIC = 'metric';

    /** The default enumeration. */
    public const self DEFAULT = self::METRIC;

    /**
     * Gets these attributes.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'system' => $this->value,
            'speed' => $this->getSpeed(),
            'temperature' => $this->getDegree(),
            'pressure' => 'hPa',
            'degree' => '°',
            'percent' => '%',
            'volume' => 'mm',
        ];
    }

    /**
     * Gets the degree units.
     */
    public function getDegree(): string
    {
        return $this->getExtraString('degree');
    }

    /**
     * Gets the speed units.
     */
    public function getSpeed(): string
    {
        return $this->getExtraString('speed');
    }

    /**
     * @return OpenWeatherUnits[]
     */
    #[\Override]
    public static function sorted(): array
    {
        return [
            self::METRIC,
            self::IMPERIAL,
        ];
    }
}
