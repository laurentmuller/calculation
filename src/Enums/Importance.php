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

use App\Interfaces\EnumDefaultInterface;
use App\Interfaces\EnumSortableInterface;
use App\Traits\EnumDefaultTrait;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\Attribute\ReadableEnum;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumInterface;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumTrait;

/**
 * The notification email importance enumeration.
 *
 * @implements EnumDefaultInterface<Importance>
 * @implements EnumSortableInterface<Importance>
 */
#[ReadableEnum(prefix: 'importance.', useValueAsDefault: true)]
enum Importance: string implements EnumDefaultInterface, EnumSortableInterface, TranslatableEnumInterface
{
    use EnumDefaultTrait;
    use TranslatableEnumTrait;

    /*
     * High importance.
     */
    case HIGH = 'high';

    /*
     * Low importance (default value).
     */
    #[EnumCase(extras: [EnumDefaultInterface::NAME => true])]
    case LOW = 'low';

    /*
     * Medium  importance.
     */
    case MEDIUM = 'medium';

    /*
     * Urgente importance.
     */
    case URGENT = 'urgent';

    /**
     * Gets the full human representation of the value (to be translated).
     */
    public function getReadableFull(): string
    {
        return $this->getReadable() . '_full';
    }

    /**
     * @return Importance[]
     */
    public static function sorted(): array
    {
        return [
            self::LOW,
            self::MEDIUM,
            self::HIGH,
            self::URGENT,
        ];
    }
}
