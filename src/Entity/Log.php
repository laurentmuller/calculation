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
 *
 * @ORM\Entity(repositoryClass="App\Repository\LogRepository")
 * @ORM\Table(name="sy_Log")
 */
class Log extends BaseEntity
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
     * @ORM\Column(name="user_name", type="string", length=180, nullable=true)
     *
     * @var string
     */
    private $userName;

    /**
     * Constructor.
     */
    public function __construct()
    {
    }

    /**
     * Gets the channel.
     *
     * @return string
     */
    public function getChannel(): ?string
    {
        return $this->channel;
    }

    /**
     * Gets the context.
     *
     * @return array
     */
    public function getContext(): ?array
    {
        return $this->context;
    }

    /**
     * Gets the creation date.
     *
     * @return \DateTime
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
     *
     * @return array
     */
    public function getExtra(): ?array
    {
        return $this->extra;
    }

    /**
     * Gets the level.
     *
     * @return string
     */
    public function getLevel(): ?string
    {
        return $this->level;
    }

    /**
     * Gets the message.
     *
     * @return string
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Gets the user name.
     *
     * @return string
     */
    public function getUserName(): ?string
    {
        return $this->userName;
    }

    /**
     * Sets the channel.
     *
     * @param string $channel
     */
    public function setChannel($channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * Sets the context.
     */
    public function setContext(array $context): self
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
    public function setExtra(array $extra): self
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * Sets the level.
     *
     * @param string $level
     */
    public function setLevel(?string $level): self
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Sets the message.
     *
     * @param string $message
     */
    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Sets the user name.
     */
    public function setUserName(string $userName): self
    {
        $this->userName = $userName;

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
