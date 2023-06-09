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
use App\Model\Comment;
use App\Traits\TranslatorAwareTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;
use Twig\Extra\Markdown\MarkdownInterface;

/**
 * Service to send notifications.
 */
class MailerService implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;
    use TranslatorAwareTrait;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly UrlGeneratorInterface $generator,
        private readonly MarkdownInterface $markdown,
        private readonly MailerInterface $mailer
    ) {
    }

    /**
     * Send a comment.
     *
     * @throws TransportExceptionInterface if an exception occurs while sending the notification
     */
    public function sendComment(Comment $comment): void
    {
        $notification = $this->createNotification($comment->getImportance());
        $notification->subject((string) $comment->getSubject())
            ->markdown($this->convert((string) $comment->getMessage()))
            ->action($this->trans('index.title'), $this->getHomeUrl())
            ->attachFromUploadedFiles(...$comment->getAttachments());
        if (($address = $comment->getFromAddress()) instanceof Address) {
            $notification->from($address);
        }
        if (($address = $comment->getToAddress()) instanceof Address) {
            $notification->to($address);
        }
        $this->mailer->send($notification);
    }

    /**
     * Send a notification.
     *
     * @param UploadedFile[] $attachments
     *
     * @throws TransportExceptionInterface if an exception occurs while sending the notification
     */
    public function sendNotification(string $fromEmail, User $toUser, string $message, Importance $importance = Importance::LOW, array $attachments = []): void
    {
        $notification = $this->createNotification($importance)
            ->from($fromEmail)
            ->to($toUser->getEmailAddress())
            ->subject($this->trans('user.comment.title'))
            ->markdown($this->convert($message));
        foreach ($attachments as $attachment) {
            $notification->attachFromUploadedFile($attachment);
        }
        $this->mailer->send($notification);
    }

    private function convert(string $message): string
    {
        return $this->markdown->convert($message);
    }

    private function createNotification(Importance $importance): NotificationEmail
    {
        return (new NotificationEmail())
            ->action($this->trans('index.title'), $this->getHomeUrl())
            ->update($importance, $this->getTranslator());
    }

    private function getHomeUrl(): string
    {
        return $this->generator->generate(AbstractController::HOME_PAGE, [], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
