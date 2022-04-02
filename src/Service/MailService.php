<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extra\Markdown\MarkdownInterface;

/**
 * @author Laurent Muller
 */
class MailService
{
    use TranslatorTrait;

    private string $homeUrl;
    private ?MarkdownInterface $markdown = null;

    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator, private MailerInterface $mailer, UrlGeneratorInterface $generator, private string $appNameVersion)
    {
        $this->homeUrl = $generator->generate(AbstractController::HOME_PAGE, [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->setTranslator($translator);
    }

    /**
     * Send a comment.
     *
     * @throws TransportExceptionInterface if an exception occurs while sending the comment
     */
    public function sendComment(string $fromEmail, User $toUser, string $message, string $importance = NotificationEmail::IMPORTANCE_LOW): void
    {
        $importance = $this->trans("importance.full.$importance");
        $message = "<p style=\"font-weight: bold;\">$importance</p>$message";

        $comment = new Comment(true);
        $comment->setFromAddress($fromEmail)
            ->setToAddress($toUser)
            ->setSubject($this->trans('user.comment.title'))
            ->setMessage($message);
        $comment->send($this->mailer);
    }

    /**
     * Send a notification.
     *
     * @throws TransportExceptionInterface if an exception occurs while sending the notification
     */
    public function sendNotification(string $fromEmail, User $toUser, string $message, string $importance = NotificationEmail::IMPORTANCE_LOW): void
    {
        $notification = new NotificationEmail($this->translator);
        $notification->from($fromEmail)
            ->to($toUser->getAddress())
            ->importance($importance)
            ->subject($this->trans('user.comment.title'))
            ->markdown($this->convert($message))
            ->setFooterText($this->appNameVersion)
            ->action($this->trans('index.title'), $this->homeUrl);

        $this->mailer->send($notification);
    }

    /**
     * Set the markdown used to convert the message content.
     */
    public function setMarkdown(MarkdownInterface $markdown): self
    {
        $this->markdown = $markdown;

        return $this;
    }

    private function convert(string $message): string
    {
        if (null !== $this->markdown) {
            return $this->markdown->convert($message);
        }

        return \strip_tags($message);
    }
}
