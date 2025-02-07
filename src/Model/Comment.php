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

namespace App\Model;

use App\Entity\User;
use App\Enums\Importance;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Address;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represent a comment to send.
 */
class Comment
{
    /**
     * The attachments.
     *
     * @var ?UploadedFile[]
     */
    #[Assert\Count(max: 3)]
    #[Assert\All([new Assert\File(maxSize: 10_485_760)])]
    private ?array $attachments = null;

    /**
     * The address from.
     */
    #[Assert\NotNull]
    private ?Address $fromAddress = null;

    /*
     * The importance.
     */
    #[Assert\NotNull]
    private Importance $importance;

    /**
     * The message.
     */
    #[Assert\NotNull]
    private ?string $message = null;

    /**
     * The subject.
     */
    #[Assert\NotNull]
    private ?string $subject = null;

    /**
     * The address to.
     */
    #[Assert\NotNull]
    private ?Address $toAddress = null;

    /**
     * @param bool $mail true to send an email, false to send a comment
     */
    public function __construct(private readonly bool $mail = true)
    {
        $this->importance = Importance::getDefault();
    }

    /**
     * Gets the file attachments.
     *
     * @return UploadedFile[]
     */
    public function getAttachments(): array
    {
        return $this->attachments ?? [];
    }

    /**
     * Gets the "from" address.
     */
    public function getFromAddress(): ?Address
    {
        return $this->fromAddress;
    }

    public function getImportance(): Importance
    {
        return $this->importance;
    }

    /**
     * Gets the message.
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Gets the subject.
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * Gets the "to" address.
     */
    public function getToAddress(): ?Address
    {
        return $this->toAddress;
    }

    /**
     * Returns if this is an email or a comment.
     *
     * @return bool true if is an email, false if is a comment
     */
    public function isMail(): bool
    {
        return $this->mail;
    }

    /**
     * Sets the file attachments.
     *
     * @param UploadedFile[] $attachments
     */
    public function setAttachments(?array $attachments): self
    {
        $this->attachments = $attachments;

        return $this;
    }

    /**
     * Sets the "from" address.
     *
     * @throws \InvalidArgumentException if the parameter is not an instanceof of Address, User or string
     */
    public function setFromAddress(Address|User|string $fromAddress): self
    {
        if ($fromAddress instanceof User) {
            $this->fromAddress = $fromAddress->getEmailAddress();
        } else {
            $this->fromAddress = Address::create($fromAddress);
        }

        return $this;
    }

    public function setImportance(Importance $importance): self
    {
        $this->importance = $importance;

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
     * Sets the subject.
     */
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Sets the "to" address.
     *
     * @throws \InvalidArgumentException if the parameter is not an instanceof of Address, User or string
     */
    public function setToAddress(Address|User|string $toAddress): self
    {
        if ($toAddress instanceof User) {
            $this->toAddress = $toAddress->getEmailAddress();
        } else {
            $this->toAddress = Address::create($toAddress);
        }

        return $this;
    }
}
