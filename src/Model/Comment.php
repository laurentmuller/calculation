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
use SimpleHtmlToText\Parser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represent a comment (e-mail) to send.
 */
class Comment
{
    /**
     * The attachments.
     *
     * @var ?UploadedFile[]
     */
    #[Assert\Count(max: 3)]
    #[Assert\All(new Assert\File(maxSize: 10485760))]
    private ?array $attachments = null;

    /**
     * The from address.
     */
    #[Assert\NotNull]
    private ?Address $fromAddress = null;

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
     * The to address.
     */
    #[Assert\NotNull]
    private ?Address $toAddress = null;

    /**
     * Constructor.
     *
     * @param bool $mail true to send an e-mail, false to send a comment
     */
    public function __construct(private readonly bool $mail)
    {
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
     * Returns if this is an e-mail or a comment.
     *
     * @return bool True if e-mail, false if comment
     */
    public function isMail(): bool
    {
        return $this->mail;
    }

    /**
     * Sends this message using the given mailer.
     *
     * @throws TransportExceptionInterface if the email can not be sent
     */
    public function send(MailerInterface $mailer): void
    {
        $email = new Email();
        if (null !== $this->fromAddress) {
            $email->addFrom($this->fromAddress);
        }
        if (null !== $this->toAddress) {
            $email->addTo($this->toAddress);
        }
        $email->subject((string) $this->subject)
            ->text($this->getTextMessage())
            ->html($this->getHtmlMessage());

        // add attachments
        foreach ($this->getAttachments() as $attachment) {
            $this->addAttachment($email, $attachment);
        }

        // send
        $mailer->send($email);
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
            $this->fromAddress = $fromAddress->getAddress();
        } else {
            $this->fromAddress = Address::create($fromAddress);
        }

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
            $this->toAddress = $toAddress->getAddress();
        } else {
            $this->toAddress = Address::create($toAddress);
        }

        return $this;
    }

    /**
     * Adds the given uploaded file as attachment to the given email.
     */
    private function addAttachment(Email $email, ?UploadedFile $file): Email
    {
        if (null !== $file && $file->isValid()) {
            $path = $file->getPathname();
            $name = $file->getClientOriginalName();
            $type = $file->getClientMimeType();

            return $email->attachFromPath($path, $name, $type);
        }

        return $email;
    }

    /**
     * Remove empty lines for the given message.
     */
    private function getHtmlMessage(): string
    {
        /** @var string[] $lines */
        $lines = (array) \preg_split('/\r\n|\r|\n/', (string) $this->message);
        $result = \array_filter($lines, static fn (string $line): bool => !empty($line) && 0 !== \strcasecmp('<p>&nbsp;</p>', $line));

        return \implode('', $result);
    }

    /**
     * Convert the given message as plain text.
     */
    private function getTextMessage(): string
    {
        $parser = new Parser();
        $message = $this->message;

        return $parser->parseString($message);
    }
}
