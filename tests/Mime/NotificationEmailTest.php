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
        $email = $this->createNotificationEmail();
        $email->importance(Importance::MEDIUM);
        $context = $email->getContext();

        self::assertArrayHasKey('importance', $context);
        self::assertSame('medium', $context['importance']);

        self::assertArrayHasKey('importance_text', $context);
        self::assertSame('importance.medium_full', $context['importance_text']);
    }

    public function testImportanceAsEnumValue(): void
    {
        $email = $this->createNotificationEmail();
        $email->importance(Importance::MEDIUM->value);
        $context = $email->getContext();

        self::assertArrayHasKey('importance', $context);
        self::assertSame('medium', $context['importance']);

        self::assertArrayHasKey('importance_text', $context);
        self::assertSame('importance.medium_full', $context['importance_text']);
    }

    public function testImportanceAsOtherString(): void
    {
        $email = $this->createNotificationEmail();
        $email->importance('fake');
        $context = $email->getContext();

        self::assertArrayHasKey('importance', $context);
        self::assertSame('fake', $context['importance']);
        self::assertArrayNotHasKey('importance_text', $context);
    }

    public function testPreparedHeadersWithoutSubject(): void
    {
        $email = $this->createNotificationEmail();
        $email->from('fake@fake.com')
            ->to('fake@fake.com');
        $headers = $email->getPreparedHeaders();

        $expected = '[LOW] ';
        $actual = $headers->getHeaderBody('Subject');
        self::assertSame($expected, $actual);
    }

    public function testPreparedHeadersWithSubject(): void
    {
        $email = $this->createNotificationEmail();
        $email->subject('subject')
            ->from('fake@fake.com')
            ->to('fake@fake.com');
        $email->importance(Importance::MEDIUM);
        $headers = $email->getPreparedHeaders();

        $expected = 'subject - importance.medium_full';
        $actual = $headers->getHeaderBody('Subject');
        self::assertSame($expected, $actual);
    }

    public function testTranslatableSubject(): void
    {
        $email = $this->createNotificationEmail();
        $email->subject(new TranslatableMessage('user.comment.title'));
        $email->from('fake@fake.com')
            ->to('fake@fake.com');
        $headers = $email->getPreparedHeaders();

        $expected = '[LOW] user.comment.title';
        $actual = $headers->getHeaderBody('Subject');
        self::assertSame($expected, $actual);
    }

    private function createNotificationEmail(): NotificationEmail
    {
        return NotificationEmail::create($this->createMockTranslator());
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
