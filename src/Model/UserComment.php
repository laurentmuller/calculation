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
class UserComment
{
    /**
     * The attachments.
     *
     * @var ?UploadedFile[]
     */
    #[Assert\Count(max: 3)]
    #[Assert\All([new Assert\File(maxSize: 10_485_760)])]
    private ?array $attachments = null;

    /** The address from. */
    #[Assert\NotNull]
    private ?Address $from = null;

    /*
     * The importance.
     */
    #[Assert\NotNull]
    private Importance $importance;

    /** The message. */
    #[Assert\NotNull]
    private ?string $message = null;

    /** The subject. */
    #[Assert\NotNull]
    private ?string $subject = null;

    /** The address to. */
    #[Assert\NotNull]
    private ?Address $to = null;

    public function __construct()
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
    public function getFrom(): ?Address
    {
        return $this->from;
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
    public function getTo(): ?Address
    {
        return $this->to;
    }

    /**
     * Create a new instance.
     *
     * @throws \InvalidArgumentException if the <code>\$from</code> or <code>\$to</code> parameters cannot be converted
     *                                   to an Address
     */
    public static function instance(
        string $subject,
        Address|User|string $from,
        Address|User|string $to
    ): self {
        $instance = new self();

        return $instance->setSubject($subject)
            ->setFrom($from)
            ->setTo($to);
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
     * @throws \InvalidArgumentException if the given parameter cannot be converted to an Address
     */
    public function setFrom(Address|User|string $from): self
    {
        $this->from = $this->convertAddress($from);

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
     * @throws \InvalidArgumentException if the given parameter cannot be converted to an Address
     */
    public function setTo(Address|User|string $to): self
    {
        $this->to = $this->convertAddress($to);

        return $this;
    }

    private function convertAddress(string|Address|User $address): Address
    {
        return $address instanceof User ? $address->getAddress() : Address::create($address);
    }
}
