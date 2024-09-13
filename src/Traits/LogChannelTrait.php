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
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait to handle channel icons.
 */
trait LogChannelTrait
{
    /**
     * The long application channel name.
     */
    private const APP_CHANNEL_LONG = 'application';

    /**
     * The short application channel name.
     */
    private const APP_CHANNEL_SHORT = 'app';

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50)]
    private string $channel = self::APP_CHANNEL_LONG;

    /**
     * Gets the channel's icon.
     *
     * @param bool $capitalize true to capitalize this channel's name
     */
    public function getChannel(bool $capitalize = false): string
    {
        return $capitalize ? \ucfirst($this->channel) : $this->channel;
    }

    /**
     * Get the channel icon class.
     */
    public function getChannelIcon(): string
    {
        return 'fa-fw fa-solid fa-' . match ($this->channel) {
            'application' => 'laptop-code',
            'cache' => 'hard-drive',
            'console' => 'keyboard',
            'doctrine' => 'database',
            'mailer' => 'envelope',
            'php' => 'code',
            'request' => 'upload',
            'security' => 'key',
            'deprecation' => 'bug',
            default => 'file',
        };
    }

    public function isChannel(): bool
    {
        return '' !== $this->channel;
    }

    public function setChannel(string $channel): self
    {
        $channel = \strtolower($channel);
        $this->channel = self::APP_CHANNEL_SHORT === $channel ? self::APP_CHANNEL_LONG : $channel;

        return $this;
    }
}
