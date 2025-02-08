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

namespace App\Tests\Controller;

use App\Enums\Importance;
use App\Service\MailerService;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extra\Markdown\MarkdownInterface;

class CommentControllerTest extends ControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/user/comment', self::ROLE_USER];
        yield ['/user/comment', self::ROLE_ADMIN];
        yield ['/user/comment', self::ROLE_SUPER_ADMIN];
    }

    public function testInvoke(): void
    {
        $this->checkForm(
            uri: 'user/comment',
            id: 'common.button_send',
            data: [
                'user_comment[subject]' => 'subject',
                'user_comment[importance]' => Importance::MEDIUM->value,
                'user_comment[message]' => 'message',
            ],
            userName: self::ROLE_USER
        );
    }

    public function testInvokeWithException(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->method('send')
            ->willThrowException(new TransportException());
        $markdown = $this->createMock(MarkdownInterface::class);
        $service = new MailerService(
            $this->getService(UrlGeneratorInterface::class),
            $markdown,
            $mailer,
            $this->getService(TranslatorInterface::class)
        );
        $this->setService(MailerService::class, $service);

        $this->checkForm(
            uri: 'user/comment',
            id: 'common.button_send',
            data: [
                'user_comment[subject]' => 'subject',
                'user_comment[importance]' => Importance::MEDIUM->value,
                'user_comment[message]' => 'message',
            ],
            userName: self::ROLE_USER,
            followRedirect: false,
            disableReboot: true
        );
    }
}
