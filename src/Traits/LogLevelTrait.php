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

namespace App\Traits;

use App\Enums\FontAwesomePath;
use App\Model\FontAwesomeIcon;
use Doctrine\ORM\Mapping as ORM;
use Psr\Log\LogLevel as PsrLevel;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait to handle log level icons and colors.
 */
trait LogLevelTrait
{
    /** @phpstan-var PsrLevel::* */
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[ORM\Column(length: 20)]
    private string $level = PsrLevel::INFO;

    /**
     * Get the icon and color classes.
     */
    public function getFullLevelIcon(): string
    {
        return $this->getLevelIcon() . ' ' . $this->getLevelColor();
    }

    /**
     * Get the level.
     *
     * @return PsrLevel::*
     */
    public function getLevel(): string
    {
        return $this->level;
    }

    /**
     * Get the level border color class.
     */
    public function getLevelBorder(): string
    {
        return $this->getColor('text-border-');
    }

    /**
     * Get the level color class.
     */
    public function getLevelColor(): string
    {
        return $this->getColor('text-');
    }

    /**
     * Get the level FontAwesome icon.
     */
    public function getLevelFontAwesomeIcon(): FontAwesomeIcon
    {
        return match ($this->level) {
            PsrLevel::ALERT,
            PsrLevel::CRITICAL,
            PsrLevel::EMERGENCY,
            PsrLevel::ERROR => new FontAwesomeIcon(FontAwesomePath::SOLID, 'circle-exclamation'),
            PsrLevel::WARNING => new FontAwesomeIcon(FontAwesomePath::SOLID, 'triangle-exclamation'),
            default => new FontAwesomeIcon(FontAwesomePath::SOLID, 'circle-info'),
        };
    }

    /**
     * Get the level icon class.
     */
    public function getLevelIcon(): string
    {
        return $this->getLevelFontAwesomeIcon()
            ->asHtml('fa-fw');
    }

    /**
     * Gets the level with the first character uppercase.
     */
    public function getLevelTitle(): string
    {
        return \ucfirst($this->level);
    }

    /**
     * @phpstan-param PsrLevel::* $level
     */
    public function setLevel(string $level): self
    {
        $this->level = \strtolower($level);

        return $this;
    }

    private function getColor(string $prefix): string
    {
        return $prefix . match ($this->level) {
            PsrLevel::ALERT,
            PsrLevel::CRITICAL,
            PsrLevel::EMERGENCY,
            PsrLevel::ERROR => 'danger',
            PsrLevel::WARNING => 'warning',
            PsrLevel::DEBUG => 'secondary',
            default => 'info'
        };
    }
}
