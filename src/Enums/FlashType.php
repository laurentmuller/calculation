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

use App\Interfaces\EnumTranslatableInterface;
use App\Traits\EnumTranslatableTrait;
use Elao\Enum\Attribute\ReadableEnum;
use Elao\Enum\ExtrasTrait;

/**
 * Flash bag type enumeration.
 */
#[ReadableEnum(prefix: 'flash_bag.', useValueAsDefault: true)]
enum FlashType: string implements EnumTranslatableInterface
{
    use EnumTranslatableTrait;
    use ExtrasTrait;

    /*
     * Danger flash bag.
     */
    case DANGER = 'danger';

    /*
     * Information  flash bag.
     */
    case INFO = 'info';

    /*
     * Success flash bag.
     */
    case SUCCESS = 'success';

    /*
     * Warning flash bag.
     */
    case WARNING = 'warning';
}
