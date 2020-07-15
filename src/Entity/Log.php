<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Represents an application log entry.
 */
class Log extends AbstractEntity
{
    /**
     * @ORM\Column(name="channel", type="string", length=50)
     *
     * @var string
     */
    private $channel;

    /**
     * @ORM\Column(name="context", type="array", nullable=true)
     *
     * @var array
     */
    private $context;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     *
     * @var \DateTime
     */
    private $createdAt;

    /**
     * @ORM\Column(name="extra", type="array", nullable=true)
     *
     * @var array
     */
    private $extra;

    /**
     * @ORM\Column(name="level", type="string", length=50)
     *
     * @var string
     */
    private $level;

    /**
     * @ORM\Column(name="message", type="text")
     *
     * @var string
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
