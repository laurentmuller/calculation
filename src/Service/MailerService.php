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

namespace App\Service;

use App\Controller\AbstractController;
use App\Entity\User;
use App\Enums\Importance;
use App\Mime\NotificationEmail;
use App\Model\UserComment;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extra\Markdown\MarkdownInterface;

/**
 * Service to send comments and notifications.
 */
readonly class MailerService
{
    public function __construct(
        private UrlGeneratorInterface $generator,
        private MarkdownInterface $markdown,
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \InvalidArgumentException if the address parameter cannot be converted to an Address
     */
    public static function convertAddress(string|Address|User $address): Address
    {
        return $address instanceof User ? $address->getAddress() : Address::create($address);
    }

    /**
     * Send a comment.
     *
     * @throws \InvalidArgumentException   if the subject or the message are null, if the sender or the recipient are
     *                                     null or cannot be converted to an Address
     * @throws TransportExceptionInterface if an exception occurs while sending the comment
     */
    public function sendComment(UserComment $comment): void
    {
        if (null === $comment->getSubject()) {
            throw new \InvalidArgumentException('The comment must have a subject.');
        }
        if (null === $comment->getMessage()) {
            throw new \InvalidArgumentException('The comment must have a message.');
        }
        if (!$comment->getFrom() instanceof Address) {
            throw new \InvalidArgumentException('The comment must have a sender.');
        }
        if (!$comment->getTo() instanceof Address) {
            throw new \InvalidArgumentException('The comment must have a recipient.');
        }

        $notification = $this->createNotification($comment->getImportance(), $comment->getMessage())
            ->attachFromUploadedFiles(...$comment->getAttachments())
            ->from(self::convertAddress($comment->getFrom()))
            ->to(self::convertAddress($comment->getTo()))
            ->subject($comment->getSubject())
            ->setSignature(false);

        $this->send($notification);
    }

    /**
     * Send a notification.
     *
     * @param UploadedFile[] $attachments
     *
     * @throws \InvalidArgumentException   if the sender or the recipient cannot be converted to an Address
     * @throws TransportExceptionInterface if an exception occurs while sending the notification
     */
    public function sendNotification(
        string|Address|User $from,
        string|Address|User $to,
        string $message,
        Importance $importance = Importance::DEFAULT,
        array $attachments = [],
        bool $signature = true,
    ): void {
        $notification = $this->createNotification($importance, $message)
            ->subject(new TranslatableMessage('user.comment.title'))
            ->attachFromUploadedFiles(...$attachments)
            ->from(self::convertAddress($from))
            ->to(self::convertAddress($to))
            ->setSignature($signature);

        $this->send($notification);
    }

    private function convertToMarkdown(string $message): string
    {
        return $this->markdown->convert($message);
    }

    private function createNotification(Importance $importance, string $message): NotificationEmail
    {
        return NotificationEmail::instance($this->translator)
            ->action($this->getActionText(), $this->getActionURL())
            ->markdown($this->convertToMarkdown($message))
            ->importance($importance);
    }

    private function getActionText(): string
    {
        return $this->translator->trans('index.title');
    }

    private function getActionURL(): string
    {
        return $this->generator->generate(
            name: AbstractController::HOME_PAGE,
            referenceType: UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function send(NotificationEmail $notification): void
    {
        $this->mailer->send($notification);
    }
}
