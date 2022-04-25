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

use App\Service\LogService;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents an application log entry.
 *
 * @author Laurent Muller
 */
class Log extends AbstractEntity
{
    /**
     * @ORM\Column(type="string", length=50)
     */
    private ?string $channel = null;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private ?array $context = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private \DateTimeInterface $createdAt;

    /**
     * @ORM\Column(type="array", nullable=true)
     */
    private ?array $extra = null;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private ?string $level = null;

    /**
     * @ORM\Column(type="text")
     */
    private ?string $message = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * Gets the channel.
     */
    public function getChannel(): ?string
    {
        return $this->channel;
    }

    /**
     * Gets the color depending on the level.
     */
    public function getColor(): string
    {
        return match ($this->level) {
            'debug' => 'var(--secondary)',
            'warning' => 'var(--warning)',
            'error', 'critical', 'alert', 'emergency' => 'var(--danger)',
            default => 'var(--info)',
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
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplay(): string
    {
        return $this->message ?? parent::getDisplay();
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
        return LogService::getCreatedAt($this->createdAt);
    }

    /**
     * Gets the level.
     */
    public function getLevel(): ?string
    {
        return $this->level;
    }

    /**
     * Gets the message.
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Sets the channel.
     */
    public function setChannel(string $channel): self
    {
        $this->channel = $channel;

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
    public function setCreatedAt(\DateTimeInterface $createdAt): self
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
     * Sets the primary key identifier.
     *
     * Used only when create a log from a file.
     *
     * @param int $id the key identifier to set
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
        $this->level = $level;

        return $this;
    }

    /**
     * Sets the message.
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }
}
