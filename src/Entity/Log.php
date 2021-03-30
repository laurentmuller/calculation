<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Entity;

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
     *
     * @var string
     */
    private $channel;

    /**
     * @ORM\Column(type="array", nullable=true)
     *
     * @var array
     */
    private $context;

    /**
     * @ORM\Column(type="datetime")
     *
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @ORM\Column(type="array", nullable=true)
     *
     * @var array
     */
    private $extra;

    /**
     * @ORM\Column(type="string", length=50)
     *
     * @var string
     */
    private $level;

    /**
     * @ORM\Column(type="text")
     *
     * @var ?string
     */
    private $message;

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
     * Gets the color depending of the level.
     */
    public function getColor(): string
    {
        switch ($this->level) {
            case 'debug':
                return 'var(--secondary)';
            case 'warning':
                return 'var(--warning)';
            case 'error':
            case 'critical':
            case 'alert':
            case 'emergency':
                return 'var(--danger)';
            case 'info':
            case 'notice':
            default:
                return 'var(--info)';
        }
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
    public function getCreatedAt(): ?\DateTime
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
     * Gets the extra informations.
     */
    public function getExtra(): ?array
    {
        return $this->extra;
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
    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Sets the extra informations.
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

    /**
     * {@inheritdoc}
     */
    protected function getSearchTerms(): array
    {
        return [
            $this->channel,
            $this->level,
            $this->message,
        ];
    }
}
