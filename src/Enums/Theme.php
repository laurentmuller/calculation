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
use App\Interfaces\EnumTranslatableInterface;
use App\Traits\EnumDefaultTrait;
use App\Traits\EnumTranslatableTrait;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\Attribute\ReadableEnum;

/**
 * Theme style enumeration.
 *
 * @implements EnumDefaultInterface<Theme>
 * @implements EnumSortableInterface<Theme>
 */
#[ReadableEnum(prefix: 'theme.', suffix: '.name', useValueAsDefault: true)]
enum Theme: string implements EnumDefaultInterface, EnumSortableInterface, EnumTranslatableInterface
{
    use EnumDefaultTrait;
    use EnumTranslatableTrait;

    /*
     * The dark theme.
     */
    #[EnumCase(extras: ['icon' => 'fa-regular fa-moon'])]
    case DARK = 'dark';

    /*
     * The light theme.
     */
    #[EnumCase(extras: ['icon' => 'fa-regular fa-sun', EnumDefaultInterface::NAME => true])]
    case LIGHT = 'light';

    /**
     * Gets the icon.
     */
    public function getIcon(): string
    {
        return $this->getExtraString('icon');
    }

    /**
     * Gets the success message (to be translated).
     */
    public function getSuccess(): string
    {
        return \sprintf('theme.%s.success', $this->value);
    }

    /**
     * Gets the title (to be translated).
     */
    public function getTitle(): string
    {
        return \sprintf('theme.%s.title', $this->value);
    }

    /**
     * @return Theme[]
     */
    public static function sorted(): array
    {
        return [
            Theme::LIGHT,
            Theme::DARK,
        ];
    }
}
