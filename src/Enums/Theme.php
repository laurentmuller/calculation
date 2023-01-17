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
use App\Interfaces\SortableEnumInterface;
use App\Traits\DefaultEnumTrait;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\Attribute\ReadableEnum;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

/**
 * Theme style enumeration.
 *
 * @implements SortableEnumInterface<Theme>
 */
#[ReadableEnum(prefix: 'theme.', suffix: '.name', useValueAsDefault: true)]
enum Theme: string implements DefaultEnumInterface, ReadableEnumInterface, SortableEnumInterface
{
    use DefaultEnumTrait;
    use ReadableEnumTrait;

    /*
     * The dark theme.
     */
    #[EnumCase(extras: [
        'icon' => 'fa-solid fa-moon',
        'title' => 'theme.dark.title',
        'success' => 'theme.dark.success',
        'css' => 'js/vendor/bootstrap/css/bootstrap-dark.css', ])]
    case DARK = 'dark';
    /*
     * The light theme.
     */
    #[EnumCase(extras: [
        'default' => true,
        'icon' => 'fa-regular fa-sun',
        'title' => 'theme.light.title',
        'success' => 'theme.light.success',
        'css' => 'js/vendor/bootstrap/css/bootstrap-light.css', ])]
    case LIGHT = 'light';

    /**
     * Gets the CSS style sheet.
     */
    public function getCss(): string
    {
        return (string) $this->getExtra('css');
    }

    /**
     * Gets the icon.
     */
    public function getIcon(): string
    {
        return (string) $this->getExtra('icon');
    }

    /**
     * Gets the success message (to be translated).
     */
    public function getSuccess(): string
    {
        return (string) $this->getExtra('success');
    }

    /**
     * Gets the title (to be translated).
     */
    public function getTitle(): string
    {
        return (string) $this->getExtra('title');
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
