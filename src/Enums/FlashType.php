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
use App\Traits\EnumExtrasTrait;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\Attribute\ReadableEnum;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumInterface;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumTrait;

/**
 * Flash bag type enumeration.
 *
 * @implements DefaultEnumInterface<FlashType>
 */
#[ReadableEnum(prefix: 'flash_bag.', useValueAsDefault: true)]
enum FlashType: string implements DefaultEnumInterface, TranslatableEnumInterface
{
    use EnumExtrasTrait;
    use TranslatableEnumTrait;

    #[EnumCase(extras: ['icon' => 'fa-solid fa-lg fa-exclamation-triangle'])]
    case DANGER = 'danger';

    #[EnumCase(extras: ['icon' => 'fa-solid fa-lg fa-info-circle'])]
    case INFO = 'info';

    #[EnumCase(extras: ['icon' => 'fa-solid fa-lg fa-check-circle'])]
    case SUCCESS = 'success';

    #[EnumCase(extras: ['icon' => 'fa-solid fa-lg fa-exclamation-circle'])]
    case WARNING = 'warning';

    /** The default enumeration. */
    public const self DEFAULT = self::SUCCESS;

    /**
     * Gets the icon color.
     */
    public function getColor(): string
    {
        return \sprintf('text-%s', $this->value);
    }

    /**
     * Gets the Font Awesome icon.
     */
    public function getIcon(): string
    {
        return $this->getExtraString('icon');
    }
}
