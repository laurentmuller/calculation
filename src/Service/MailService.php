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
    private string $appNameVersion;
    private string $homeUrl;

    private MailerInterface $mailer;
    private ?MarkdownInterface $markdown = null;

    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator, MailerInterface $mailer, UrlGeneratorInterface $generator, string $appNameVersion)
    {
        $this->translator = $translator;
        $this->mailer = $mailer;
        $this->appNameVersion = $appNameVersion;
        $this->homeUrl = $generator->generate(AbstractController::HOME_PAGE, [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Send a comment.
     *
     * @throws TransportExceptionInterface if an exception occurs while sending the comment
     */
    public function sendComment(User $user, string $email, string $message, string $importance): void
    {
        $importance = $this->trans("importance.full.$importance");
        $message = "<p style=\"font-weight: bold;\">$importance</p>$message";

        $comment = new Comment(true);
        $comment->setFromAddress($email)
            ->setToAddress($user)
            ->setSubject($this->trans('user.comment.title'))
            ->setMessage($message);
        $comment->send($this->mailer);
    }

    /**
     * Send a notification.
     *
     * @throws TransportExceptionInterface if an exception occurs while sending the notification
     */
    public function sendNotification(User $user, string $email, string $message, string $importance): void
    {
        $notification = new NotificationEmail($this->translator);
        $notification->from($email)
            ->to($user->getAddress())
            ->importance($importance)
            ->subject($this->trans('user.comment.title'))
            ->markdown($this->convert($message))
            ->action($this->trans('index.title_help'), $this->homeUrl)
            ->setFooterText($this->appNameVersion);

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
