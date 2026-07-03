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

namespace App\Tests\Traits;

use App\Enums\Theme;
use App\Traits\CookieTrait;
use fpdf\Enums\PdfPageMode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CookieTraitTest extends TestCase
{
    use CookieTrait;

    private const string COOKIE_KEY = 'key';

    public function testGetCookieBoolean(): void
    {
        $request = $this->createRequest();
        self::assertFalse($this->getCookieBoolean($request, self::COOKIE_KEY));
        self::assertTrue($this->getCookieBoolean($request, self::COOKIE_KEY, default: true));

        $request = $this->createRequest(false);
        self::assertFalse($this->getCookieBoolean($request, self::COOKIE_KEY));

        $request = $this->createRequest(true);
        self::assertTrue($this->getCookieBoolean($request, self::COOKIE_KEY));
    }

    public function testGetCookieEnum(): void
    {
        $request = $this->createRequest();
        self::assertSame(Theme::DEFAULT, $this->getCookieEnum($request, self::COOKIE_KEY, Theme::DEFAULT));

        $request = $this->createRequest(Theme::DARK);
        $actual = $this->getCookieEnum($request, self::COOKIE_KEY, Theme::DEFAULT);
        self::assertSame(Theme::DARK, $actual); // @phpstan-ignore staticMethod.impossibleType
    }

    public function testGetCookieFloat(): void
    {
        $request = $this->createRequest();
        self::assertSame(0.0, $this->getCookieFloat($request, self::COOKIE_KEY));
        self::assertSame(1.0, $this->getCookieFloat($request, self::COOKIE_KEY, default: 1.0));

        $request = $this->createRequest(2.0);
        self::assertSame(2.0, $this->getCookieFloat($request, self::COOKIE_KEY));
    }

    public function testGetCookieInt(): void
    {
        $request = $this->createRequest();
        self::assertSame(0, $this->getCookieInt($request, self::COOKIE_KEY));
        self::assertSame(1, $this->getCookieInt($request, self::COOKIE_KEY, default: 1));

        $request = $this->createRequest(2);
        self::assertSame(2, $this->getCookieInt($request, self::COOKIE_KEY));
    }

    public function testGetCookieString(): void
    {
        $request = $this->createRequest();
        self::assertSame('', $this->getCookieString($request, self::COOKIE_KEY));
        self::assertSame('default', $this->getCookieString($request, self::COOKIE_KEY, default: 'default'));

        $request = $this->createRequest('My String');
        self::assertSame('My String', $this->getCookieString($request, self::COOKIE_KEY));
    }

    public function testUpdateCookie(): void
    {
        $response = $this->createResponse();
        $this->updateCookie($response, self::COOKIE_KEY, null);
        self::assertSameCookie($response, null);

        $response = $this->createResponse();
        $this->updateCookie($response, self::COOKIE_KEY, 'value');
        self::assertSameCookie($response, 'value');

        $response = $this->createResponse();
        $this->updateCookie($response, self::COOKIE_KEY, Theme::DARK);
        self::assertSameCookie($response, Theme::DARK);

        $response = $this->createResponse();
        $this->updateCookie($response, self::COOKIE_KEY, true);
        self::assertSameCookie($response, true);

        $response = $this->createResponse();
        $this->updateCookie($response, self::COOKIE_KEY, Theme::DEFAULT);
        self::assertSameCookie($response, null);

        $response = $this->createResponse();
        $this->updateCookie($response, self::COOKIE_KEY, PdfPageMode::getDefault());
        self::assertSameCookie($response, null);
    }

    public function testUpdateCookies(): void
    {
        $values = [
            'str' => 'value1',
            'bool' => true,
            'int' => 10,
            'float' => 10.0,
            'enum' => Theme::DARK,
            'null' => null,
        ];
        $response = $this->createResponse();
        $cookies = $response->headers->getCookies();
        self::assertCount(0, $cookies);
        $this->updateCookies($response, $values);
        $cookies = $response->headers->getCookies();
        self::assertCount(6, $cookies);
    }

    protected static function assertSameCookie(Response $response, mixed $value): void
    {
        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        } elseif (\is_bool($value)) {
            $value = \json_encode($value);
        }
        $cookies = $response->headers->getCookies();
        self::assertCount(1, $cookies);
        self::assertSame($value, $cookies[0]->getValue());
    }

    #[\Override]
    protected function getCookiePath(): string
    {
        return '/';
    }

    private function createRequest(string|bool|int|float|\BackedEnum|null $value = null): Request
    {
        if (null === $value) {
            return new Request();
        }

        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }
        $cookies = [\strtoupper(self::COOKIE_KEY) => $value];

        return new Request(cookies: $cookies);
    }

    private function createResponse(): Response
    {
        return new Response();
    }
}
