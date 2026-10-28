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

final class CspReportControllerTest extends ControllerTestCase
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

    public function testWithNoCspReport(): void
    {
        $this->invoke('{}');
    }

    public function testWithTransportException(): void
    {
        $exception = new UnexpectedResponseException('Fake Message');
        $mailer = self::createStub(MailerInterface::class);
        $mailer->method('send')
            ->willThrowException($exception);
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
            [
                {
                    "type": "csp-violation",
                    "age": 53531,
                    "url": "https://example.com/signup",
                    "user_agent": "Mozilla/5.0",
                    "body": {
                        "documentURL": "https://example.com/signup",
                        "blockedURL": "https://apis.google.com/js/platform.js",
                        "effectiveDirective": "script-src-elem",
                        "originalPolicy": "script-src-elem 'none'; form-action 'self'",
                        "disposition": "report",
                        "statusCode": 200,
                        "sample": "This a sample."
                    }
                },
                {
                    "type": "permissions-policy-violation",
                    "age": 48512,
                    "url": "https://example.com/",
                    "user_agent": "Mozilla/5.0",
                    "body": {
                        "columnNumber": 29,
                        "disposition": "enforce",
                        "lineNumber": 44,
                        "message": "Permissions policy violation: geolocation access has been blocked because of a permissions policy applied to the current document.",
                        "featureId": "geolocation",
                        "sourceFile": "https://example.com/"
                    }
                }
            ]    
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
                expected: Response::HTTP_NO_CONTENT,
            );
        } finally {
            MockPhpStream::restore();
        }
    }
}
