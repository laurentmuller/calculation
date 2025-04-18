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

use App\Tests\MockPhpStream;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Symfony\Component\Mailer\MailerInterface;

class CspReportControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/csp', self::ROLE_USER, Response::HTTP_NO_CONTENT];
    }

    public function testWithEmptyContent(): void
    {
        $this->invoke('');
    }

    public function testWithInvalidContent(): void
    {
        $this->invoke('{');
    }

    public function testWithTransportException(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->method('send')
            ->willThrowException(new UnexpectedResponseException('Fake Message'));
        $this->setService(MailerInterface::class, $mailer);

        $this->invoke($this->getValidContent());
    }

    public function testWithValidContent(): void
    {
        $this->invoke($this->getValidContent());
    }

    private function getValidContent(): string
    {
        return <<<'JSON'
              {
                "csp-report": {
                    "blocked-uri": "https://example.com/css/style.css",
                    "disposition": "report",
                    "document-uri": "https://example.com/signup.html",
                    "effective-directive": "style-src-elem",
                    "original-policy": "default-src 'none'; style-src cdn.example.com; report-uri /_/csp-reports",
                    "referrer": "",
                    "status-code": 200,
                    "violated-directive": "style-src-elem"
                }
            }
            JSON;
    }

    private function invoke(string $content): void
    {
        try {
            MockPhpStream::register();
            \file_put_contents('php://input', $content);

            $this->checkRoute(
                url: '/csp',
                username: self::ROLE_USER,
                expected: Response::HTTP_NO_CONTENT
            );
        } finally {
            MockPhpStream::restore();
        }
    }
}
