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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait to handle channel icons.
 */
trait LogChannelTrait
{
    // the long application channel name
    private const string APP_CHANNEL_LONG = 'application';
    // the short application channel name
    private const string APP_CHANNEL_SHORT = 'app';

    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[ORM\Column(length: 20)]
    private string $channel = self::APP_CHANNEL_LONG;

    /**
     * Gets the channel's icon.
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * Get the channel FontAwesome icon.
     */
    public function getChannelFontAwesomeIcon(): FontAwesomeIcon
    {
        return match ($this->channel) {
            'application' => new FontAwesomeIcon(FontAwesomePath::SOLID, 'laptop-code'),
            'cache' => new FontAwesomeIcon(FontAwesomePath::SOLID, 'hard-drive'),
            'console' => new FontAwesomeIcon(FontAwesomePath::SOLID, 'keyboard'),
            'doctrine' => new FontAwesomeIcon(FontAwesomePath::SOLID, 'database'),
            'mailer' => new FontAwesomeIcon(FontAwesomePath::SOLID, 'envelope'),
            'php' => new FontAwesomeIcon(FontAwesomePath::SOLID, 'code'),
            'request' => new FontAwesomeIcon(FontAwesomePath::SOLID, 'upload'),
            'security' => new FontAwesomeIcon(FontAwesomePath::SOLID, 'key'),
            'deprecation' => new FontAwesomeIcon(FontAwesomePath::SOLID, 'bug'),
            default => new FontAwesomeIcon(FontAwesomePath::SOLID, 'file'),
        };
    }

    /**
     * Get the channel icon class.
     */
    public function getChannelIcon(): string
    {
        return $this->getChannelFontAwesomeIcon()
            ->asHtml('fa-fw');
    }

    /**
     * Gets the channel with the first character uppercase.
     */
    public function getChannelTitle(): string
    {
        return \ucfirst($this->channel);
    }

    public function setChannel(string $channel): self
    {
        $channel = \strtolower($channel);
        $this->channel = self::APP_CHANNEL_SHORT === $channel ? self::APP_CHANNEL_LONG : $channel;

        return $this;
    }
}
