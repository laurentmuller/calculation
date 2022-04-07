<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Model;

use App\Service\ThemeService;
use App\Util\FileUtils;

/**
 * Represents a Bootstrap theme.
 *
 * @see https://bootswatch.com/
 *
 * @author Laurent Muller
 */
class Theme
{
    /**
     * The dark theme names.
     */
    private const DARK_THEMES = [
        'Cyborg',
        'Darkly',
        'Slate',
        'Solar',
        'Superhero',
    ];

    /**
     * The style sheet path.
     */
    private readonly string $css;

    /**
     * The dark style.
     */
    private readonly bool $dark;

    /**
     * The description.
     */
    private readonly string $description;

    /**
     * The path exist.
     */
    private readonly bool $exist;

    /**
     * The name.
     */
    private readonly string $name;

    /**
     * Constructor.
     *
     * @param array $source the source to copy values from
     * @psalm-param array{name: string, description: string, css: string} $source
     */
    public function __construct(array $source)
    {
        $this->name = $source['name'];
        $this->description = $source['description'];
        $this->css = $source['css'];
        $this->exist = FileUtils::exists($this->css);
        $this->dark = \in_array($this->name, self::DARK_THEMES, true);
    }

    /**
     * Returns if this style sheet path exist.
     */
    public function exists(): bool
    {
        return $this->exist;
    }

    /**
     * Gets the style sheet path.
     */
    public function getCss(): string
    {
        return $this->css;
    }

    /**
     * Gets the description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Gets the name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns if this theme has the dark style.
     */
    public function isDark(): bool
    {
        return $this->dark;
    }

    /**
     * Returns if this theme is the default theme (Boostrap).
     */
    public function isDefault(): bool
    {
        return ThemeService::DEFAULT_NAME === $this->name;
    }
}
