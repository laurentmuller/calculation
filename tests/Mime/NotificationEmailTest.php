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
use App\Mime\CspViolationEmail;
use App\Mime\NotificationEmail;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[CoversClass(NotificationEmail::class)]
class NotificationEmailTest extends TestCase
{
    use TranslatorMockTrait;

    public function testAttachFromUploadedFile(): void
    {
        $mail = new CspViolationEmail();
        $mail->attachFromUploadedFile(null);
        self::assertCount(0, $mail->getAttachments());

        $file = new UploadedFile(
            path: __FILE__,
            originalName: \basename(__FILE__),
            test: true
        );
        $mail->attachFromUploadedFile($file);
        self::assertCount(1, $mail->getAttachments());
    }

    public function testAttachFromUploadedFiles(): void
    {
        $mail = new CspViolationEmail();
        self::assertCount(0, $mail->getAttachments());

        $file = new UploadedFile(
            path: __FILE__,
            originalName: \basename(__FILE__),
            test: true
        );
        $mail->attachFromUploadedFiles(null, $file);
        self::assertCount(1, $mail->getAttachments());
    }

    public function testConstructor(): void
    {
        $mail = new NotificationEmail();
        $actual = $mail->getHtmlTemplate();
        $expected = 'notification/notification.html.twig';
        self::assertSame($expected, $actual);
    }

    public function testPreparedHeaders(): void
    {
        $mail = new NotificationEmail();
        $translator = $this->createMockTranslator();
        $mail->subject('subject')
            ->from('fake@fake.com')
            ->to('fake@fake.com');
        $mail->update(Importance::MEDIUM, $translator);
        $headers = $mail->getPreparedHeaders();

        $actual = $headers->getHeaderBody('Subject');
        $expected = 'subject - importance.medium_full';
        self::assertSame($expected, $actual);
    }

    public function testUpdate(): void
    {
        $mail = new NotificationEmail();
        $translator = $this->createMockTranslator();
        $mail->update(Importance::MEDIUM, $translator);
        $context = $mail->getContext();

        self::assertArrayHasKey('importance', $context);
        self::assertSame('medium', $context['importance']);

        self::assertArrayHasKey('importance_text', $context);
        self::assertSame('importance.medium_full', $context['importance_text']);
    }
}
