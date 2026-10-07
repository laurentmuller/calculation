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
use App\Service\ApplicationService;
use App\Tests\TranslatorStubTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Translation\TranslatableMessage;

final class NotificationEmailTest extends TestCase
{
    use TranslatorStubTrait;

    public function testAttachFromUploadedFile(): void
    {
        $email = $this->createNotificationEmail();
        $email->attachFromUploadedFile(null);
        self::assertCountAttachments($email, 0);

        $file = $this->createUploadedFile();
        $email->attachFromUploadedFile($file);
        self::assertCountAttachments($email, 1);
    }

    public function testAttachFromUploadedFiles(): void
    {
        $email = $this->createNotificationEmail();
        self::assertCountAttachments($email, 0);

        $file = $this->createUploadedFile();
        $email->attachFromUploadedFiles(null, $file);
        self::assertCountAttachments($email, 1);
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
        self::assertArrayHasKey('importance_title', $context);
        self::assertSame('importance.medium_title', $context['importance_title']);
    }

    public function testImportanceAsEnumValue(): void
    {
        $email = $this->createNotificationEmail()
            ->importance(Importance::MEDIUM->value);
        $context = $email->getContext();
        self::assertArrayHasKey('importance', $context);
        self::assertSame('medium', $context['importance']);
        self::assertArrayHasKey('importance_title', $context);
        self::assertSame('importance.medium_title', $context['importance_title']);
    }

    public function testImportanceInvalid(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Invalid importance value: "fake".');
        $this->createNotificationEmail()
            ->importance('fake');
    }

    public function testPreparedHeadersWithoutSubject(): void
    {
        $email = $this->createNotificationEmail()
            ->from('fake@fake.com')
            ->to('fake@fake.com');
        $headers = $email->getPreparedHeaders();

        $expected = ApplicationService::APP_FULL_NAME . ' - importance.low_title';
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

    public function testSignature(): void
    {
        $email = $this->createNotificationEmail();
        self::assertTrue($email->isSignature());
        $email->setSignature(false);
        self::assertFalse($email->isSignature());
    }

    public function testTranslatableSubject(): void
    {
        $email = $this->createNotificationEmail()
            ->subject(new TranslatableMessage('user.comment.title'))
            ->from('fake@fake.com')
            ->to('fake@fake.com');
        $headers = $email->getPreparedHeaders();

        $expected = 'user.comment.title - importance.low_title';
        $actual = $headers->getHeaderBody('Subject');
        self::assertSame($expected, $actual);
    }

    public function testWithMediumImportance(): void
    {
        $notification = new NotificationEmail(
            translator: $this->createStubTranslator(),
            importance: Importance::MEDIUM
        );
        $notification->from('fake@fake.com')
            ->to('fake@fake.com');

        $actual = $notification->getImportanceTitle();
        self::assertSame('importance.medium_title', $actual);

        $headers = $notification->getPreparedHeaders();
        self::assertTrue($headers->has('Subject'));

        $subject = $headers->getHeaderBody('Subject');
        $expected = ApplicationService::APP_FULL_NAME . ' - importance.medium_title';
        self::assertSame($expected, $subject);
    }

    protected static function assertCountAttachments(NotificationEmail $email, int $expected): void
    {
        self::assertCount($expected, $email->getAttachments());
    }

    private function createNotificationEmail(): NotificationEmail
    {
        return NotificationEmail::instance($this->createStubTranslator());
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
