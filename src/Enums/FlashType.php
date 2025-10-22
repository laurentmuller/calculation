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

use App\Traits\EnumExtrasTrait;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\Attribute\ReadableEnum;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumInterface;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumTrait;

/**
 * Flash bag type enumeration.
 */
#[ReadableEnum(prefix: 'flash_bag.', useValueAsDefault: true)]
enum FlashType: string implements TranslatableEnumInterface
{
    use EnumExtrasTrait;
    use TranslatableEnumTrait;

    /**
     * Danger flash bag.
     */
    #[EnumCase(extras: ['icon' => 'fas fa-lg fa-exclamation-triangle'])]
    case DANGER = 'danger';

    /**
     * Information flash bag.
     */
    #[EnumCase(extras: ['icon' => 'fas fa-lg fa-info-circle'])]
    case INFO = 'info';

    /**
     * Success flash bag.
     */
    #[EnumCase(extras: ['icon' => 'fas fa-lg fa-check-circle'])]
    case SUCCESS = 'success';

    /**
     * Warning flash-bag.
     */
    #[EnumCase(extras: ['icon' => 'fas fa-lg fa-exclamation-circle'])]
    case WARNING = 'warning';

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
