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

use App\Interfaces\SortableEnumInterface;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

/**
 * The notification email importance enumeration.
 *
 *  @implements SortableEnumInterface<Importance>
 */
enum Importance: string implements ReadableEnumInterface, SortableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('importance.high')]
    case HIGH = 'high';
    #[EnumCase('importance.low')]
    case LOW = 'low';
    #[EnumCase('importance.medium')]
    case MEDIUM = 'medium';
    #[EnumCase('importance.urgent')]
    case URGENT = 'urgent';
    /**
     * @return Importance[]
     */
    public static function sorted(): array
    {
        return [
            Importance::LOW,
            Importance::MEDIUM,
            Importance::HIGH,
            Importance::URGENT,
        ];
    }
}
