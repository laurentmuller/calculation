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

    /**
     * The user extra field name.
     */
    final public const USER_FIELD = 'user';

    /**
     * The doctrine channel name.
     */
    private const DOCTRINE_CHANNEL = 'doctrine';

    #[ORM\Column(nullable: true)]
    private ?array $context = null;

    #[Assert\NotNull]
    #[ORM\Column(type: DatePointType::NAME)]
    private DatePoint $createdAt;

    /**
     * @var ?array<string, string>
     */
    #[ORM\Column(nullable: true)]
    private ?array $extra = null;

    private ?string $formattedDate = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $message = '';

    public function __construct()
    {
        $this->createdAt = DateUtils::createDatePoint();
    }

    #[\Override]
    public function compare(ComparableInterface $other): int
    {
        $result = $this->getCreatedAt() <=> $other->getCreatedAt();

        return 0 !== $result ? $result : $this->getId() <=> $other->getId();
    }

    public static function formatDate(DatePoint $date): string
    {
        return FormatUtils::formatDateTime($date, \IntlDateFormatter::SHORT, \IntlDateFormatter::MEDIUM);
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
        if (null !== $this->context && [] !== $this->context) {
            $message .= "\nContext:\n" . StringUtils::exportVar($this->getContext());
        }
        if (null !== $this->extra && [] !== $this->extra) {
            $message .= "\nExtra:\n" . StringUtils::exportVar($this->getExtra());
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

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Gets the creation date as the unix timestamp.
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

    /**
     * @param ?array<string, string> $extra
     */
    public function setExtra(?array $extra): self
    {
        $this->extra = $extra;

        return $this;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }
}
