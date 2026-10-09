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
use App\Service\ApplicationService;
use App\Service\MailerService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Address;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represent a comment to send.
 */
class UserComment
{
    /** @var ?UploadedFile[] */
    #[Assert\Count(max: 3)]
    #[Assert\All([new Assert\File(maxSize: 10_485_760)])]
    private ?array $attachments = null;

    #[Assert\NotNull]
    private ?Address $from = null;

    #[Assert\NotNull]
    private Importance $importance = Importance::DEFAULT;

    #[Assert\NotBlank]
    private ?string $message = null;

    #[Assert\NotBlank]
    private ?string $subject = null;

    #[Assert\NotNull]
    private ?Address $to = null;

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
     * Gets the sender address.
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
     * Gets the recipient address.
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
        Address|User|string|null $from,
        Address|User|string|null $to,
        string $subject = ApplicationService::APP_FULL_NAME
    ): self {
        $comment = (new self())->setSubject($subject);
        if (null !== $from) {
            $comment->setFrom($from);
        }
        if (null !== $to) {
            $comment->setTo($to);
        }

        return $comment;
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
     * Sets the sender address.
     *
     * @throws \InvalidArgumentException if the sender cannot be converted to an Address
     */
    public function setFrom(Address|User|string $from): self
    {
        $this->from = MailerService::convertAddress($from);

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
     * Sets the recipient address.
     *
     * @throws \InvalidArgumentException if the recipient cannot be converted to an Address
     */
    public function setTo(Address|User|string $to): self
    {
        $this->to = MailerService::convertAddress($to);

        return $this;
    }
}
