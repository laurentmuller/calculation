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
use App\Model\UserComment;
use App\Service\MailerService;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extra\Markdown\MarkdownInterface;

final class MailerServiceTest extends TestCase
{
    use TranslatorMockTrait;

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
        self::expectNotToPerformAssertions();
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
        self::expectNotToPerformAssertions();
    }

    private function createAttachement(): UploadedFile
    {
        return new UploadedFile(__FILE__, \basename(__FILE__), test: true);
    }

    private function createService(): MailerService
    {
        return new MailerService(
            self::createStub(UrlGeneratorInterface::class),
            self::createStub(MarkdownInterface::class),
            self::createStub(MailerInterface::class),
            $this->createMockTranslator()
        );
    }
}
