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

namespace App\Entity;

use App\Service\ThemeService;
use Symfony\Component\Filesystem\Filesystem;

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
     *
     * @var string[]
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
     *
     * @var string
     */
    protected $css;

    /**
     * The dark style.
     *
     * @var bool|null
     */
    protected $dark;

    /**
     * The description.
     *
     * @var string
     */
    protected $description;

    /**
     * The path exist.
     *
     * @var bool|null
     */
    protected $exist;

    /**
     * The name.
     *
     * @var string
     */
    protected $name;

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
            $fs = new Filesystem();
            $this->exist = $fs->exists($this->css);
        }

        return $this->exist;
    }

    /**
     * Gets the style sheet path.
     *
     * @return string
     */
    public function getCss(): ?string
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
     *
     * @return string
     */
    public function getName(): ?string
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
