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
     * Send a comment.
     *
     * @throws TransportExceptionInterface if an exception occurs while sending the comment
     */
    public function sendComment(UserComment $comment): void
    {
        /** @var string $message */
        $message = $comment->getMessage();
        /** @var string $subject */
        $subject = $comment->getSubject();
        /** @var Address $from */
        $from = $comment->getFrom();
        /** @var Address $to */
        $to = $comment->getTo();

        $notification = $this->createNotification($comment->getImportance(), $message)
            ->attachFromUploadedFiles(...$comment->getAttachments())
            ->subject($subject)
            ->from($from)
            ->to($to);

        $this->send($notification);
    }

    /**
     * Send a notification.
     *
     * @param UploadedFile[] $attachments
     *
     * @throws TransportExceptionInterface if an exception occurs while sending the notification
     */
    public function sendNotification(
        string|Address|User $from,
        string|Address|User $to,
        string $message,
        Importance $importance = Importance::LOW,
        array $attachments = []
    ): void {
        $notification = $this->createNotification($importance, $message)
            ->subject(new TranslatableMessage('user.comment.title'))
            ->attachFromUploadedFiles(...$attachments)
            ->from($from)
            ->to($to);

        $this->send($notification);
    }

    private function createNotification(Importance $importance, string $message): NotificationEmail
    {
        return NotificationEmail::instance($this->translator)
            ->action($this->getActionText(), $this->getActionURL())
            ->markdown($this->getMarkdown($message))
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

    private function getMarkdown(string $message): string
    {
        return $this->markdown->convert($message);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function send(NotificationEmail $notification): void
    {
        $this->mailer->send($notification);
    }
}
