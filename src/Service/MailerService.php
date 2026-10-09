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
use App\Model\UserComment;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
     * @throws \InvalidArgumentException if the parameter cannot be converted to an Address
     */
    public static function convertAddress(string|Address|User $address): Address
    {
        return $address instanceof User ? $address->getAddress() : Address::create($address);
    }

    /**
     * Send a comment.
     *
     * @throws \InvalidArgumentException   if the sender (from), the recipient (to), the subject or the message is null
     * @throws TransportExceptionInterface if an exception occurs while sending the comment
     */
    public function sendComment(UserComment $comment): void
    {
        $notification = $this->createNotification($comment);
        $this->mailer->send($notification);
    }

    private function convertToMarkdown(string $message): string
    {
        return $this->markdown->convert($message);
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function createNotification(UserComment $comment): NotificationEmail
    {
        $this->validateComment($comment);

        return NotificationEmail::instance($this->translator)
            ->from(self::convertAddress($comment->getFrom()))
            ->to(self::convertAddress($comment->getTo()))
            ->importance($comment->getImportance())
            ->subject($comment->getSubject())
            ->markdown($this->convertToMarkdown($comment->getMessage()))
            ->action($this->getActionText(), $this->getActionURL())
            ->attachFromUploadedFiles(...$comment->getAttachments())
            ->setSignature(false);
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
     * @throws \InvalidArgumentException
     *
     * @phpstan-assert Address $comment->getFrom()
     * @phpstan-assert Address $comment->getTo()
     * @phpstan-assert string $comment->getSubject()
     * @phpstan-assert string $comment->getMessage()
     */
    private function validateComment(UserComment $comment): void
    {
        if (!$comment->getFrom() instanceof Address) {
            throw new \InvalidArgumentException('The comment must have a sender.');
        }
        if (!$comment->getTo() instanceof Address) {
            throw new \InvalidArgumentException('The comment must have a recipient.');
        }
        if (null === $comment->getSubject()) {
            throw new \InvalidArgumentException('The comment must have a subject.');
        }
        if (null === $comment->getMessage()) {
            throw new \InvalidArgumentException('The comment must have a message.');
        }
    }
}
