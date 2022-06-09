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
use App\Mime\NotificationEmail;
use App\Model\Comment;
use App\Traits\TranslatorTrait;
use Symfony\Bridge\Twig\Mime\NotificationEmail as NotificationEmailAlias;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extra\Markdown\MarkdownInterface;

class MailerService
{
    use TranslatorTrait;

    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator, private readonly UrlGeneratorInterface $generator, private readonly MarkdownInterface $markdown, private readonly MailerInterface $mailer, private readonly string $appNameVersion)
    {
        $this->translator = $translator;
    }

    /**
     * Send a comment.
     *
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface if an exception occurs while sending the notification
     */
    public function sendComment(Comment $comment): void
    {
        $notification = $this->createNotification();
        $notification->importance($comment->getImportance())
            ->subject((string) $comment->getSubject())
            ->markdown($this->convert((string) $comment->getMessage()))
            ->action($this->trans('index.title'), $this->getHomeUrl());

        $address = $comment->getFromAddress();
        if (null !== $address) {
            $notification->from($address);
        }
        $address = $comment->getToAddress();
        if (null !== $address) {
            $notification->to($address);
        }
        foreach ($comment->getAttachments() as $attachment) {
            $notification->attachFromUploadedFile($attachment);
        }
        $this->mailer->send($notification);
    }

    /**
     * Send a notification.
     *
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface if an exception occurs while sending the notification
     */
    public function sendNotification(string $fromEmail, User $toUser, string $message, string $importance = NotificationEmailAlias::IMPORTANCE_LOW): void
    {
        $notification = $this->createNotification()
            ->from($fromEmail)
            ->to($toUser->getAddress())
            ->subject($this->trans('user.comment.title'))
            ->markdown($this->convert($message))
            ->importance($importance);

        $this->mailer->send($notification);
    }

    private function convert(string $message): string
    {
        return $this->markdown->convert($message);
    }

    private function createNotification(): NotificationEmail
    {
        $notification = new NotificationEmail($this->translator);
        $notification->setFooterText($this->getFooterText())
            ->action($this->trans('index.title'), $this->getHomeUrl());

        return $notification;
    }

    private function getFooterText(): string
    {
        return $this->trans('notification.footer', ['%name%' => $this->appNameVersion]);
    }

    private function getHomeUrl(): string
    {
        return $this->generator->generate(AbstractController::HOME_PAGE, [], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
