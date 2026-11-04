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
use App\Traits\ClosureSortTrait;
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
    use ClosureSortTrait;
    use LogChannelTrait;
    use LogLevelTrait;

    // the doctrine channel name
    private const string DOCTRINE_CHANNEL = 'doctrine';
    // the doctrine prefix message
    private const string DOCTRINE_PREFIX = 'executing ';

    /** The context. */
    #[ORM\Column(nullable: true)]
    public ?array $context = null;

    /** The creation date. */
    #[Assert\NotNull]
    #[ORM\Column(type: DatePointType::NAME)]
    public DatePoint $createdAt;

    /** The formatted date. */
    public string $formattedDate = '' {
        get => '' !== $this->formattedDate
            ? $this->formattedDate
            : $this->formattedDate = self::formatDate($this->createdAt);
    }

    /** The message. */
    #[ORM\Column(type: Types::TEXT)]
    public string $message = '';

    /** Gets the creation date as the Unix timestamp. */
    public int $timestamp {
        get => $this->createdAt->getTimestamp();
    }

    /** The username. */
    #[ORM\Column(length: UserInterface::MAX_USERNAME_LENGTH, nullable: true)]
    public ?string $user = null {
        set => StringUtils::trim($value);
    }

    public function __construct()
    {
        $this->createdAt = DateUtils::createDatePoint();
    }

    #[\Override]
    public function compare(ComparableInterface $other): int
    {
        return $this->compareByClosures(
            $this,
            $other,
            static fn (Log $a, Log $b): int => $a->createdAt <=> $b->createdAt,
            static fn (Log $a, Log $b): int => $a->id <=> $b->id,
        );
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
        $message = $this->message;
        if ($this->isDoctrineMessage()) {
            $message = $formatter->format($message);
        }
        if (StringUtils::isString($this->user)) {
            $message .= "\nUser:\n" . $this->user;
        }
        if (null !== $this->context && [] !== $this->context) {
            $message .= "\nContext:\n" . StringUtils::exportVar($this->context);
        }

        return $message;
    }

    #[\Override]
    public function getDisplay(): string
    {
        return $this->message;
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
            && StringUtils::startWith($this->message, self::DOCTRINE_PREFIX);
    }

    public function withContext(?array $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function withCreatedAt(DatePoint $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function withMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function withUser(?string $user): self
    {
        $this->user = $user;

        return $this;
    }
}
