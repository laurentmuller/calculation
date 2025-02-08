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

use App\Entity\User;
use App\Enums\Importance;
use App\Model\Comment;
use App\Service\MailerService;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extra\Markdown\MarkdownInterface;

class MailerServiceTest extends TestCase
{
    use TranslatorMockTrait;

    /**
     * @throws TransportExceptionInterface
     */
    public function testSendComment(): void
    {
        $comment = new Comment();
        $comment->setFromAddress('from@example.com')
            ->setToAddress('to@example.com')
            ->setSubject('subject')
            ->setMessage('message')
            ->setAttachments([$this->createAttachement()]);

        $service = $this->createService();
        $service->sendComment($comment);
        self::assertTrue(true);
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testSendNotification(): void
    {
        $toUser = new User();
        $toUser->setUsername('username')
            ->setEmail('to@example.com');
        $service = $this->createService();
        $service->sendNotification(
            'from@example.com',
            $toUser,
            'message',
            Importance::LOW,
            [$this->createAttachement()]
        );
        self::assertTrue(true);
    }

    private function createAttachement(): UploadedFile
    {
        return new UploadedFile(__FILE__, \basename(__FILE__), test: true);
    }

    private function createService(): MailerService
    {
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $markdown = $this->createMock(MarkdownInterface::class);
        $mailer = $this->createMock(MailerInterface::class);
        $translator = $this->createMockTranslator();

        return new MailerService(
            $generator,
            $markdown,
            $mailer,
            $translator
        );
    }
}
