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
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class NotificationEmailTest extends TestCase
{
    use TranslatorMockTrait;

    public function testAttachFromUploadedFile(): void
    {
        $mail = CspViolationEmail::create();
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
        $mail = CspViolationEmail::create();
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
        $actual = NotificationEmail::create()
            ->getHtmlTemplate();
        $expected = 'notification/notification.html.twig';
        self::assertSame($expected, $actual);
    }

    public function testPreparedHeaders(): void
    {
        $mail = NotificationEmail::create();
        $translator = $this->createMockTranslator();
        $mail->subject('subject')
            ->from('fake@fake.com')
            ->to('fake@fake.com');
        $mail->updateImportance(Importance::MEDIUM, $translator);
        $headers = $mail->getPreparedHeaders();

        $actual = $headers->getHeaderBody('Subject');
        $expected = 'subject - importance.medium_full';
        self::assertSame($expected, $actual);
    }

    public function testUpdate(): void
    {
        $mail = NotificationEmail::create();
        $translator = $this->createMockTranslator();
        $mail->updateImportance(Importance::MEDIUM, $translator);
        $context = $mail->getContext();

        self::assertArrayHasKey('importance', $context);
        self::assertSame('medium', $context['importance']);

        self::assertArrayHasKey('importance_text', $context);
        self::assertSame('importance.medium_full', $context['importance_text']);
    }
}
