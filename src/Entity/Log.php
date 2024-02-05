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

use App\Utils\FormatUtils;
use App\Utils\StringUtils;
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
     * The user extra field name.
     */
    final public const USER_FIELD = 'user';

    /**
     * The long application channel name.
     */
    private const APP_CHANNEL_LONG = 'application';

    /**
     * The short application channel name.
     */
    private const APP_CHANNEL_SHORT = 'app';

    /**
     * The doctrine channel name.
     */
    private const DOCTRINE_CHANNEL = 'doctrine';

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50)]
    private string $channel = self::APP_CHANNEL_LONG;

    #[ORM\Column(nullable: true)]
    private ?array $context = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeInterface $createdAt;

    /**
     * @var ?array<string, string>
     */
    #[ORM\Column(nullable: true)]
    private ?array $extra = null;

    private ?string $formattedDate = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[ORM\Column(length: 50)]
    private string $level = LogLevel::INFO;

    #[ORM\Column(type: Types::TEXT)]
    private string $message = '';

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function formatDate(\DateTimeInterface $date): string
    {
        return FormatUtils::formatDateTime($date, \IntlDateFormatter::SHORT, \IntlDateFormatter::MEDIUM);
    }

    /**
     * Gets the message with the context and extra properties if available.
     *
     * @psalm-api
     */
    public function formatMessage(SqlFormatter $formatter): string
    {
        $message = $this->getMessage();
        if (self::DOCTRINE_CHANNEL === $this->getChannel()) {
            $message = $formatter->format($message);
        }
        if (null !== $this->context && [] !== $this->context) {
            $message .= "\nContext:\n" . StringUtils::exportVar($this->getContext());
        }
        if (null !== $this->extra && [] !== $this->extra) {
            $message .= "\nExtra:\n" . StringUtils::exportVar($this->getExtra());
        }

        return $message;
    }

    public function getChannel(bool $capitalize = false): string
    {
        return $capitalize ? StringUtils::capitalize($this->channel) : $this->channel;
    }

    /**
     * Gets the channel's icon.
     *
     * @psalm-api
     */
    public function getChannelIcon(): string
    {
        return match ($this->channel) {
            'application' => 'fa-fw fa-solid fa-laptop-code',
            'cache' => 'fa-fw fa-solid fa-hard-drive',
            'console' => 'fa-fw fa-regular fa-keyboard',
            'doctrine' => 'fa-fw fa-solid fa-database',
            'mailer' => 'fa-fw fa-regular fa-envelope',
            'php' => 'fa-fw fa-solid fa-code',
            'request' => 'fa-fw fa-solid fa-code-pull-request',
            'security' => 'fa-fw fa-solid fa-key',
            default => 'fa-fw fa-solid fa-file',
        };
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getDisplay(): string
    {
        return $this->getMessage();
    }

    public function getExtra(): ?array
    {
        return $this->extra;
    }

    public function getFormattedDate(): string
    {
        if (null === $this->formattedDate) {
            $this->formattedDate = self::formatDate($this->createdAt);
        }

        return $this->formattedDate;
    }

    public function getLevel(bool $capitalize = false): string
    {
        return $capitalize ? StringUtils::capitalize($this->level) : $this->level;
    }

    /**
     * @psalm-api
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
     * @psalm-api
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

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Gets the creation date as unix timestamp.
     */
    public function getTimestamp(): int
    {
        return $this->getCreatedAt()->getTimestamp();
    }

    public function getUser(): ?string
    {
        return $this->extra[self::USER_FIELD] ?? null;
    }

    /**
     * Create an instance of Log.
     *
     * @param int|null $id the optional primary key identifier
     */
    public static function instance(?int $id = null): self
    {
        $log = new self();
        $log->id = $id;

        return $log;
    }

    public function isChannel(): bool
    {
        return !empty($this->channel);
    }

    public function isLevel(): bool
    {
        return !empty($this->level);
    }

    public function setChannel(string $channel): self
    {
        $channel = \strtolower($channel);
        $this->channel = self::APP_CHANNEL_SHORT === $channel ? self::APP_CHANNEL_LONG : $channel;

        return $this;
    }

    public function setContext(?array $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @param ?array<string, string> $extra
     */
    public function setExtra(?array $extra): self
    {
        $this->extra = $extra;

        return $this;
    }

    public function setLevel(string $level): self
    {
        $this->level = \strtolower($level);

        return $this;
    }

    public function setMessage(string $message): self
    {
        $this->message = \trim($message);

        return $this;
    }
}
