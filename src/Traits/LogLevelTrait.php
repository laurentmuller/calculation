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
use App\Pdf\Html\HtmlBootstrapColor;
use Doctrine\ORM\Mapping as ORM;
use Psr\Log\LogLevel as PsrLevel;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait to handle log level.
 */
trait LogLevelTrait
{
    /** Get the icon and color classes. */
    public string $fullLevelIcon {
        get => $this->levelIcon . ' ' . $this->levelColor;
    }
    /**
     * The level.
     *
     * @phpstan-var PsrLevel::*
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[ORM\Column(length: 20)]
    public string $level = PsrLevel::INFO {
        set => \strtolower($value);
    }

    /** Get the level boostrap color. */
    public HtmlBootstrapColor $levelBootstrapColor {
        get => match ($this->level) {
            PsrLevel::ALERT,
            PsrLevel::CRITICAL,
            PsrLevel::EMERGENCY,
            PsrLevel::ERROR => HtmlBootstrapColor::DANGER,
            PsrLevel::WARNING => HtmlBootstrapColor::WARNING,
            PsrLevel::DEBUG => HtmlBootstrapColor::SECONDARY,
            default => HtmlBootstrapColor::INFO,
        };
    }

    /** Get the level border color class. */
    public string $levelBorder {
        get => $this->getColor('text-border-');
    }

    /** Get the level color class. */
    public string $levelColor {
        get => $this->getColor('text-');
    }

    /** Get the level FontAwesome icon. */
    public FontAwesomeIcon $levelFontAwesomeIcon {
        get => match ($this->level) {
            PsrLevel::ALERT,
            PsrLevel::CRITICAL,
            PsrLevel::EMERGENCY,
            PsrLevel::ERROR => new FontAwesomeIcon(FontAwesomePath::SOLID, 'circle-exclamation'),
            PsrLevel::WARNING => new FontAwesomeIcon(FontAwesomePath::SOLID, 'triangle-exclamation'),
            default => new FontAwesomeIcon(FontAwesomePath::SOLID, 'circle-info'),
        };
    }

    /** Get the level icon class. */
    public string $levelIcon {
        get => $this->levelFontAwesomeIcon->asHtml('fa-fw');
    }

    /** Gets the level with the first character uppercase. */
    public string $levelTitle {
        get => \ucfirst($this->level);
    }

    /**
     * @phpstan-param PsrLevel::* $level
     */
    public function withLevel(string $level): self
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
