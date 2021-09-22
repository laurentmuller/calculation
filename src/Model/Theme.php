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
class Theme implements \JsonSerializable
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
    private string $css;

    /**
     * The dark style.
     */
    private ?bool $dark = null;

    /**
     * The description.
     */
    private string $description;

    /**
     * The path exist.
     */
    private ?bool $exist = null;

    /**
     * The name.
     */
    private string $name;

    /**
     * Constructor.
     *
     * @param array $source the source to copy values from
     */
    public function __construct(array $source)
    {
        $this->name = $source['name'];
        $this->description = $source['description'];
        $this->css = $source['css'];
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return \sprintf("%s('%s')", $this->name, $this->css);
    }

    /**
     * Returns if this style sheet path exist.
     *
     * @return bool true if exist
     */
    public function exists(): bool
    {
        if (null === $this->exist) {
            $this->exist = FileUtils::exists($this->css);
        }

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
     *
     * @return string
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
        if (null === $this->dark) {
            $this->dark = \in_array($this->name, self::DARK_THEMES, true);
        }

        return $this->dark;
    }

    /**
     * Returns if this theme is the default theme (Boostrap).
     */
    public function isDefault(): bool
    {
        return ThemeService::DEFAULT_NAME === $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'css' => $this->css,
        ];
    }
}
