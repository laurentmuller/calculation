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

namespace App\Tests\Service;

use App\Model\UserComment;
use App\Service\MailerService;
use App\Tests\TranslatorStubTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extra\Markdown\MarkdownInterface;

final class MailerServiceTest extends TestCase
{
    use TranslatorStubTrait;

    /**
     * @throws TransportExceptionInterface
     */
    public function testSendComment(): void
    {
        $comment = new UserComment();
        $comment->setFrom('from@example.com')
            ->setTo('to@example.com')
            ->setSubject('subject')
            ->setMessage('message')
            ->setAttachments([$this->createAttachement()]);

        $service = $this->createService();
        $service->sendComment($comment);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testSendCommentWithEmptyFrom(): void
    {
        $comment = new UserComment();
        $comment->setTo('to@example.com')
            ->setSubject('subject')
            ->setMessage('message')
            ->setAttachments([$this->createAttachement()]);
        $service = $this->createService(false);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('The comment must have a sender.');
        $service->sendComment($comment);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testSendCommentWithEmptyMessage(): void
    {
        $comment = new UserComment();
        $comment->setFrom('from@example.com')
            ->setTo('to@example.com')
            ->setSubject('subject')
            ->setAttachments([$this->createAttachement()]);
        $service = $this->createService(false);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('The comment must have a message.');
        $service->sendComment($comment);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testSendCommentWithEmptySubject(): void
    {
        $comment = new UserComment();
        $comment->setFrom('from@example.com')
            ->setTo('to@example.com')
            ->setMessage('message')
            ->setAttachments([$this->createAttachement()]);
        $service = $this->createService(false);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('The comment must have a subject.');
        $service->sendComment($comment);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testSendCommentWithEmptyTo(): void
    {
        $comment = new UserComment();
        $comment->setFrom('from@example.com')
            ->setSubject('subject')
            ->setMessage('message')
            ->setAttachments([$this->createAttachement()]);
        $service = $this->createService(false);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('The comment must have a recipient.');
        $service->sendComment($comment);
    }

    private function createAttachement(): UploadedFile
    {
        return new UploadedFile(__FILE__, \basename(__FILE__), test: true);
    }

    private function createMailer(bool $mustSend = true): MailerInterface
    {
        if ($mustSend) {
            $mailer = self::createMock(MailerInterface::class);
            $mailer->expects(self::once())
                ->method('send');

            return $mailer;
        }

        return self::createStub(MailerInterface::class);
    }

    private function createService(bool $mustSend = true): MailerService
    {
        return new MailerService(
            self::createStub(UrlGeneratorInterface::class),
            self::createStub(MarkdownInterface::class),
            $this->createMailer($mustSend),
            $this->createStubTranslator()
        );
    }
}
