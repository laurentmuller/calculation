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

use SimpleHtmlToText\Parser;
use Swift_Attachment;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represent a comment to send.
 *
 * @author Laurent Muller
 */
class Comment
{
    /**
     * The html content type.
     */
    private const HTML_CONTENT_TYPE = 'text/html';

    /**
     * The text content type.
     */
    private const TEXT_CONTENT_TYPE = 'text/plain';

    /**
     * The attachments.
     *
     * @var UploadedFile[]
     *
     * @Assert\Count(max=3)
     * @Assert\All(@Assert\File(maxSize="10485760"))
     */
    protected $attachments;

    /**
     * The sender e-mail.
     *
     * @var string
     *
     * @Assert\NotNull
     * @Assert\Email
     */
    protected $from;

    /**
     * The sender name.
     *
     * @var string
     */
    protected $fromName;

    /**
     * The message.
     *
     * @var string
     *
     * @Assert\NotNull
     */
    protected $message;

    /**
     * The subject.
     *
     * @var string
     *
     * @Assert\NotNull
     */
    protected $subject;

    /**
     * The receiver e-mail.
     *
     * @var string
     *
     * @Assert\NotNull
     * @Assert\Email
     */
    protected $to;

    /**
     * The receiver name.
     *
     * @var string
     */
    protected $toName;

    /**
     * The mail type.
     *
     * @var bool
     */
    private $mail;

    /**
     * Constructor.
     *
     * @param bool $mail true if e-mail, false if comment
     */
    public function __construct(bool $mail)
    {
        $this->mail = $mail;
    }

    /**
     * Gets the file attachments.
     *
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile[]
     */
    public function getAttachments(): array
    {
        return $this->attachments ?? [];
    }

    /**
     * Gets the "from" e-mail.
     *
     * @return string
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * Gets the "from" e-mail and name (if any).
     *
     * @return string
     */
    public function getFromFull(): ?string
    {
        if ($this->fromName) {
            return \sprintf('%s (%s)', $this->from, $this->fromName);
        }

        return $this->from;
    }

    /**
     * Gets the "from" name.
     *
     * @return string
     */
    public function getFromName(): ?string
    {
        return $this->fromName;
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
     * Gets the subject.
     *
     * @return string
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * Gets the "to" e-mail.
     *
     * @return string
     */
    public function getTo(): ?string
    {
        return $this->to;
    }

    /**
     * Gets the "to" e-mail and name (if any).
     *
     * @return string
     */
    public function getToFull(): ?string
    {
        if ($this->toName) {
            return \sprintf('%s (%s)', $this->to, $this->toName);
        }

        return $this->to;
    }

    /**
     * Gets the "to" name.
     *
     * @return string
     */
    public function getToName(): ?string
    {
        return $this->toName;
    }

    /**
     * Returns if this is an e-mail or a comment.
     *
     * @return bool true if e-mail, false if comment
     */
    public function isMail(): bool
    {
        return $this->mail;
    }

    /**
     * Sends this message using the given mailer.
     *
     * @param \Swift_Mailer $mailer the mailer service
     *
     * @return bool true if send successfully
     */
    public function send(\Swift_Mailer $mailer): bool
    {
        // create message
        $msg = new \Swift_Message();
        $msg->setFrom($this->getFromEmailAndName())
            ->setTo($this->getToEmailAndName())
            ->setSubject($this->getSubject())
            ->addPart($this->getTextMessage(), self::TEXT_CONTENT_TYPE)
            ->addPart($this->getHtmlMessage(), self::HTML_CONTENT_TYPE);

        // create attachments
        foreach ($this->getAttachments() as $attachment) {
            if ($file = $this->createAttachment($attachment)) {
                $msg->attach($file);
            }
        }

        // send
        return 0 !== $mailer->send($msg);
    }

    /**
     * Sets the file attachments.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile[] $attachments
     */
    public function setAttachments(?array $attachments): self
    {
        $this->attachments = $attachments;

        return $this;
    }

    /**
     * Sets the "from" e-mail and name.
     *
     * @param string $from the from user e-mail
     * @param string $name the from user name
     */
    public function setFrom(string $from, ?string $name = null): self
    {
        $this->from = $from;
        $this->fromName = $name;

        return $this;
    }

    /**
     * Sets the "from" user.
     *
     * @param User $user the from user
     */
    public function setFromUser(User $user): self
    {
        return $this->setFrom($user->getEmail(), $user->getUsername());
    }

    /**
     * Sets if this is a mail or a comment.
     *
     * @param bool $mail true if mail, false if comment
     */
    public function setMail(bool $mail): self
    {
        $this->mail = $mail;

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
     * Sets the "to" e-mail and name.
     *
     * @param string $to   the to user e-mail
     * @param string $name the to user name
     */
    public function setTo(string $to, ?string $name = null): self
    {
        $this->to = $to;
        $this->toName = $name;

        return $this;
    }

    /**
     * Sets the "to" user.
     *
     * @param User $user the to user
     */
    public function setToUser(User $user): self
    {
        return $this->setTo($user->getEmail(), $user->getUsername());
    }

    /**
     * Creates an attachment for the given uploaded file.
     *
     * @return Swift_Attachment|null the mailer attachment or null if the uploaded file is not valid
     */
    private function createAttachment(?UploadedFile $file): ?Swift_Attachment
    {
        if ($file && $file->isValid()) {
            $path = $file->getPathname();
            $type = $file->getClientMimeType();
            $name = $file->getClientOriginalName();

            return Swift_Attachment::fromPath($path, $type)->setFilename($name);
        }

        return null;
    }

    /**
     * Gets the "from" e-mail and name (if any).
     *
     * @return string[]|string
     */
    private function getFromEmailAndName()
    {
        if ($this->fromName) {
            return [$this->from => $this->fromName];
        }

        return $this->from;
    }

    /**
     * Remove empty lines for the given message.
     *
     * @return string the cleaned message
     */
    private function getHtmlMessage(): string
    {
        $message = $this->message;
        //$message = preg_replace('/<p><br><\/p>/', '',  $this->message);

        $lines = \preg_split('/\r\n|\r|\n/', $message);
        $result = \array_filter($lines, function ($line) {
            return !empty($line) && 0 !== \strcasecmp('<p>&nbsp;</p>', $line);
        });

        return \implode('', $result);
    }

    /**
     * Convert the given message as plain text.
     *
     * @return string the cleaned message
     */
    private function getTextMessage(): string
    {
        $parser = new Parser();
        $message = $this->message;

        return $parser->parseString($message);
    }

    /**
     * Gets the "to" e-mail and name (if any).
     *
     * @return string[]|string
     */
    private function getToEmailAndName()
    {
        if ($this->toName) {
            return [$this->to => $this->toName];
        }

        return $this->to;
    }
}
