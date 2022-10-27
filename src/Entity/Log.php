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

namespace App\Entity;

use App\Util\FormatUtils;
use App\Util\Utils;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\SqlFormatter\SqlFormatter;
use Psr\Log\LogLevel;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents an application log entry.
 */
class Log extends AbstractEntity
{
    /**
     * The application channel.
     */
    private const APP_CHANNEL = 'app';

    /**
     * The doctrine channel.
     */
    private const DOCTRINE_CHANNEL = 'doctrine';

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50)]
    private string $channel = 'application';

    #[ORM\Column(nullable: true)]
    private ?array $context = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?array $extra = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50)]
    private string $level = LogLevel::INFO;

    #[ORM\Column(type: Types::TEXT)]
    private string $message = '';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Gets the message with the context and extra properties if available.
     */
    public function formatMessage(SqlFormatter $formatter): string
    {
        $message = $this->getMessage();
        if (self::DOCTRINE_CHANNEL === $this->getChannel()) {
            $message = $formatter->format($message);
        }
        if (!empty($this->context)) {
            $message .= "\nContext:\n" . Utils::exportVar($this->getContext());
        }
        if (!empty($this->extra)) {
            $message .= "\nExtra:\n" . Utils::exportVar($this->getExtra());
        }

        return $message;
    }

    /**
     * Gets the channel.
     */
    public function getChannel(bool $capitalize = false): string
    {
        return $capitalize ? Utils::capitalize($this->channel) : $this->channel;
    }

    /**
     * Gets the channel's icon.
     */
    public function getChannelIcon(): string
    {
        return match ($this->channel) {
            'request' => 'fa-fw fa-solid fa-code-pull-request',
            'application' => 'fa-fw fa-solid fa-laptop-code',
            'doctrine' => 'fa-fw fa-solid fa-database',
            'mailer' => 'fa-fw fa-regular fa-envelope',
            'cache' => 'fa-fw fa-solid fa-hard-drive',
            'security' => 'fa-fw fa-solid fa-key',
            'php' => 'fa-fw fa-solid fa-code',
            default => 'fa-fw fa-solid fa-file',
        };
    }

    /**
     * Gets the context.
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * Gets the creation date.
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplay(): string
    {
        return $this->getMessage();
    }

    /**
     * Gets the extra information.
     */
    public function getExtra(): ?array
    {
        return $this->extra;
    }

    /**
     * Gets the formatted date.
     */
    public function getFormattedDate(): string
    {
        return (string) FormatUtils::formatDateTime($this->createdAt, \IntlDateFormatter::SHORT, \IntlDateFormatter::MEDIUM);
    }

    /**
     * Gets the level.
     */
    public function getLevel(bool $capitalize = false): string
    {
        return $capitalize ? Utils::capitalize($this->level) : $this->level;
    }

    /**
     * Gets the level color.
     */
    public function getLevelColor(): string
    {
        return match ($this->level) {
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::EMERGENCY,
            LogLevel::ERROR => 'danger',
            LogLevel::WARNING => 'warning',
            LogLevel::DEBUG => 'secondary',
            default => 'info'
        };
    }

    /**
     * Gets the level's icon.
     */
    public function getLevelIcon(): string
    {
        return match ($this->level) {
            LogLevel::ALERT,
            LogLevel::CRITICAL,
            LogLevel::EMERGENCY,
            LogLevel::ERROR => 'fa-fw fa-solid fa-circle-exclamation',
            LogLevel::WARNING => 'fa-fw fa-solid fa-triangle-exclamation',
            default => 'fa-fw fa-solid fa-circle-info',
        };
    }

    /**
     * Gets the message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Gets the user identifier.
     */
    public function getUser(): ?string
    {
        return isset($this->extra['user']) ? (string) $this->extra['user'] : null;
    }

    /**
     * Create an instance of Log.
     */
    public static function instance(): self
    {
        return new self();
    }

    public function isChannel(): bool
    {
        return !empty($this->channel);
    }

    public function isLevel(): bool
    {
        return !empty($this->level);
    }

    /**
     * Sets the channel.
     */
    public function setChannel(string $channel): self
    {
        if (self::APP_CHANNEL === $channel) {
            $channel = 'application';
        }
        $this->channel = \strtolower($channel);

        return $this;
    }

    /**
     * Sets the context.
     */
    public function setContext(?array $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Sets creation date.
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Sets the extra information.
     */
    public function setExtra(?array $extra): self
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * Sets the identifier.
     */
    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Sets the level.
     */
    public function setLevel(string $level): self
    {
        $this->level = \strtolower($level);

        return $this;
    }

    /**
     * Sets the message.
     */
    public function setMessage(string $message): self
    {
        $this->message = \trim($message);

        return $this;
    }
}
