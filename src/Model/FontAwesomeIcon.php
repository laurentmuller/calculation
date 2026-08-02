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

namespace App\Model;

use App\Enums\FontAwesomePath;

/**
 * Contains a Font Awesome icon.
 */
readonly class FontAwesomeIcon implements \Stringable
{
    /**
     * @param FontAwesomePath $path the FontAwesome path
     * @param string          $icon the icon name without the file SVG extension
     */
    public function __construct(public FontAwesomePath $path, public string $icon)
    {
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->getAbsolutePath();
    }

    public function asHtml(string $extra = ''): string
    {
        return $this->path->asHtml($this->icon, $extra);
    }

    public function getAbsolutePath(): string
    {
        return $this->path->getIconPath($this->icon);
    }

    public function getKey(): string
    {
        return \sprintf('%s/%s', $this->path->value, $this->icon);
    }
}
