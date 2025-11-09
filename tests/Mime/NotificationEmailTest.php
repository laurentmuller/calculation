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

namespace App\Tests\Mime;

use App\Enums\Importance;
use App\Mime\NotificationEmail;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Translation\TranslatableMessage;

final class NotificationEmailTest extends TestCase
{
    use TranslatorMockTrait;

    public function testAttachFromUploadedFile(): void
    {
        $email = $this->createNotificationEmail();
        $email->attachFromUploadedFile(null);
        self::assertEmpty($email->getAttachments());

        $file = $this->createUploadedFile();
        $email->attachFromUploadedFile($file);
        self::assertCount(1, $email->getAttachments());
    }

    public function testAttachFromUploadedFiles(): void
    {
        $email = $this->createNotificationEmail();
        self::assertEmpty($email->getAttachments());

        $file = $this->createUploadedFile();
        $email->attachFromUploadedFiles(null, $file);
        self::assertCount(1, $email->getAttachments());
    }

    public function testDefaultTemplate(): void
    {
        $email = $this->createNotificationEmail();
        $expected = 'notification/notification.html.twig';
        $actual = $email->getHtmlTemplate();
        self::assertSame($expected, $actual);
    }

    public function testImportanceAsEnum(): void
    {
        $email = $this->createNotificationEmail()
            ->importance(Importance::MEDIUM);
        $context = $email->getContext();
        self::assertArrayHasKey('importance', $context);
        self::assertSame('medium', $context['importance']);
        self::assertArrayHasKey('importance_text', $context);
        self::assertSame('importance.medium_title', $context['importance_text']);
    }

    public function testImportanceAsEnumValue(): void
    {
        $email = $this->createNotificationEmail()
            ->importance(Importance::MEDIUM->value);
        $context = $email->getContext();
        self::assertArrayHasKey('importance', $context);
        self::assertSame('medium', $context['importance']);
        self::assertArrayHasKey('importance_text', $context);
        self::assertSame('importance.medium_title', $context['importance_text']);
    }

    public function testImportanceInvalid(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Invalid importance value: "fake".');
        $this->createNotificationEmail()
            ->importance('fake'); // @phpstan-ignore argument.type
    }

    public function testPreparedHeadersWithoutSubject(): void
    {
        $email = $this->createNotificationEmail()
            ->from('fake@fake.com')
            ->to('fake@fake.com');
        $headers = $email->getPreparedHeaders();

        $expected = '[LOW] ';
        $actual = $headers->getHeaderBody('Subject');
        self::assertSame($expected, $actual);
    }

    public function testPreparedHeadersWithSubject(): void
    {
        $email = $this->createNotificationEmail()
            ->subject('subject')
            ->from('fake@fake.com')
            ->to('fake@fake.com');
        $email->importance(Importance::MEDIUM);
        $headers = $email->getPreparedHeaders();

        $expected = 'subject - importance.medium_title';
        $actual = $headers->getHeaderBody('Subject');
        self::assertSame($expected, $actual);
    }

    public function testTranslatableSubject(): void
    {
        $email = $this->createNotificationEmail()
            ->subject(new TranslatableMessage('user.comment.title'))
            ->from('fake@fake.com')
            ->to('fake@fake.com');
        $headers = $email->getPreparedHeaders();

        $expected = '[LOW] user.comment.title';
        $actual = $headers->getHeaderBody('Subject');
        self::assertSame($expected, $actual);
    }

    private function createNotificationEmail(): NotificationEmail
    {
        return NotificationEmail::instance($this->createMockTranslator());
    }

    private function createUploadedFile(): UploadedFile
    {
        return new UploadedFile(
            path: __FILE__,
            originalName: \basename(__FILE__),
            test: true
        );
    }
}
