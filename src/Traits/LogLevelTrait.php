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

use Doctrine\ORM\Mapping as ORM;
use Psr\Log\LogLevel as PsrLevel;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait to handle log level icons and colors.
 */
trait LogLevelTrait
{
    /**
     * @phpstan-var PsrLevel::*
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50)]
    private string $level = PsrLevel::INFO;

    /**
     * Get the level.
     *
     * @phpstan-return PsrLevel::*
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
     * Get the level icon class.
     */
    public function getLevelIcon(): string
    {
        return 'fa-fw fa-solid fa-' . match ($this->level) {
            PsrLevel::ALERT,
            PsrLevel::CRITICAL,
            PsrLevel::EMERGENCY,
            PsrLevel::ERROR => 'circle-exclamation',
            PsrLevel::WARNING => 'triangle-exclamation',
            default => 'circle-info',
        };
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
        $this->level = $level;

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
