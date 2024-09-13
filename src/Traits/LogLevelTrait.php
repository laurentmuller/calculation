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
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50)]
    private string $level = PsrLevel::INFO;

    /**
     * Get the channel.
     *
     * @param bool $capitalize true to capitalize this level's name
     */
    public function getLevel(bool $capitalize = false): string
    {
        return $capitalize ? \ucfirst($this->level) : $this->level;
    }

    /**
     * Get the level color class.
     */
    public function getLevelColor(): string
    {
        return match ($this->level) {
            PsrLevel::ALERT,
            PsrLevel::CRITICAL,
            PsrLevel::EMERGENCY,
            PsrLevel::ERROR => 'text-danger',
            PsrLevel::WARNING => 'text-warning',
            PsrLevel::DEBUG => 'text-secondary',
            default => 'text-info'
        };
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

    public function isLevel(): bool
    {
        return '' !== $this->level;
    }

    public function setLevel(string $level): self
    {
        $this->level = \strtolower($level);

        return $this;
    }
}
