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

use App\Interfaces\EnumSortableInterface;
use App\Traits\EnumExtrasTrait;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\Attribute\ReadableEnum;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumInterface;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumTrait;
use fpdf\Interfaces\PdfEnumDefaultInterface;
use fpdf\Traits\PdfEnumDefaultTrait;

/**
 * Theme style enumeration.
 *
 * @implements PdfEnumDefaultInterface<Theme>
 * @implements EnumSortableInterface<Theme>
 */
#[ReadableEnum(prefix: 'theme.', suffix: '.name', useValueAsDefault: true)]
enum Theme: string implements EnumSortableInterface, PdfEnumDefaultInterface, TranslatableEnumInterface
{
    use EnumExtrasTrait;
    use PdfEnumDefaultTrait;
    use TranslatableEnumTrait;

    /**
     * System theme (default value).
     *
     * The system theme changes the appearance from light to dark based on the user's preferences in the operating
     * system.
     */
    #[EnumCase(extras: ['icon' => 'fa-solid fa-circle-half-stroke', PdfEnumDefaultInterface::NAME => true])]
    case AUTO = 'auto';

    /**
     * Dark theme.
     *
     * The dark theme displays a dark background with a contrasting light foreground. In the dark mode, you will
     * usually see white or light text on black or dark backgrounds.
     */
    #[EnumCase(extras: ['icon' => 'fa-regular fa-moon'])]
    case DARK = 'dark';

    /**
     * Light theme.
     *
     * The light theme displays a light background with a contrasting dark foreground. In light mode, you'll usually
     * see black or dark text on white or light backgrounds.
     */
    #[EnumCase(extras: ['icon' => 'fa-regular fa-sun'])]
    case LIGHT = 'light';

    /**
     * Gets the help message (to be translated).
     */
    public function getHelp(): string
    {
        return \sprintf('theme.%s.help', $this->value);
    }

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
            self::LIGHT,
            self::DARK,
            self::AUTO,
        ];
    }
}
