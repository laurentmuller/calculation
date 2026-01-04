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

use App\Interfaces\ComparableInterface;
use App\Interfaces\UserInterface;
use App\Traits\LogChannelTrait;
use App\Traits\LogLevelTrait;
use App\Utils\DateUtils;
use App\Utils\FormatUtils;
use App\Utils\StringUtils;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\SqlFormatter\SqlFormatter;
use Symfony\Bridge\Doctrine\Types\DatePointType;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents an application log entry.
 *
 * Compare first by the date and then by the identifier, both in ascending mode.
 *
 * @implements ComparableInterface<Log>
 */
class Log extends AbstractEntity implements ComparableInterface
{
    use LogChannelTrait;
    use LogLevelTrait;

    // the doctrine channel name
    private const DOCTRINE_CHANNEL = 'doctrine';
    // the doctrine prefix message
    private const DOCTRINE_PREFIX = 'Executing ';

    #[ORM\Column(nullable: true)]
    private ?array $context = null;

    #[Assert\NotNull]
    #[ORM\Column(type: DatePointType::NAME)]
    private DatePoint $createdAt;

    private ?string $formattedDate = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $message = '';

    #[ORM\Column(length: UserInterface::MAX_USERNAME_LENGTH, nullable: true)]
    private ?string $user = null;

    public function __construct()
    {
        $this->createdAt = DateUtils::createDatePoint();
    }

    #[\Override]
    public function compare(ComparableInterface $other): int
    {
        $result = $this->createdAt <=> $other->createdAt;

        return 0 !== $result ? $result : $this->id <=> $other->id;
    }

    public static function formatDate(DatePoint $date): string
    {
        return FormatUtils::formatDateTime($date, \IntlDateFormatter::SHORT, \IntlDateFormatter::MEDIUM);
    }

    /**
     * Gets the message with the user and context properties if available.
     */
    public function formatMessage(SqlFormatter $formatter): string
    {
        $message = $this->getMessage();
        if ($this->isDoctrineMessage()) {
            $message = $formatter->format($message);
        }
        if (StringUtils::isString($this->user)) {
            $message .= "\nUser:\n" . $this->user;
        }
        if (null !== $this->context && [] !== $this->context) {
            $message .= "\nContext:\n" . StringUtils::exportVar($this->getContext());
        }

        return $message;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function getCreatedAt(): DatePoint
    {
        return $this->createdAt;
    }

    #[\Override]
    public function getDisplay(): string
    {
        return $this->getMessage();
    }

    public function getFormattedDate(): string
    {
        return $this->formattedDate ??= self::formatDate($this->createdAt);
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Gets the creation date as the unix timestamp.
     */
    public function getTimestamp(): int
    {
        return $this->createdAt->getTimestamp();
    }

    /**
     * Gets the user identifier.
     */
    public function getUser(): ?string
    {
        return $this->user;
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

    public function isDoctrineMessage(): bool
    {
        return self::DOCTRINE_CHANNEL === $this->getChannel()
            && \str_starts_with($this->message, self::DOCTRINE_PREFIX);
    }

    public function setContext(?array $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function setCreatedAt(DatePoint $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function setUser(?string $user): self
    {
        $this->user = StringUtils::trim($user);

        return $this;
    }
}
