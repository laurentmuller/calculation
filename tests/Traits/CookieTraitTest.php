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
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CookieTraitTest extends TestCase
{
    use CookieTrait;

    public function testGetCookieBoolean(): void
    {
        $request = $this->createRequest();
        self::assertFalse($this->getCookieBoolean($request, 'key'));
        self::assertTrue($this->getCookieBoolean($request, 'key', default: true));

        $request = $this->createRequest(['KEY' => false]);
        self::assertFalse($this->getCookieBoolean($request, 'key'));

        $request = $this->createRequest(['KEY' => true]);
        self::assertTrue($this->getCookieBoolean($request, 'key'));
    }

    public function testGetCookieEnum(): void
    {
        $request = $this->createRequest();
        self::assertSame(Theme::AUTO, $this->getCookieEnum($request, 'key', Theme::AUTO));

        $request = $this->createRequest(['KEY' => Theme::DARK]);
        $actual = $this->getCookieEnum($request, 'key', Theme::AUTO);
        self::assertSame(Theme::DARK, $actual); // @phpstan-ignore staticMethod.impossibleType
    }

    public function testGetCookieFloat(): void
    {
        $request = $this->createRequest();
        self::assertSame(0.0, $this->getCookieFloat($request, 'key'));
        self::assertSame(1.0, $this->getCookieFloat($request, 'key', default: 1.0));

        $request = $this->createRequest(['KEY' => 2.0]);
        self::assertSame(2.0, $this->getCookieFloat($request, 'key'));
    }

    public function testGetCookieInt(): void
    {
        $request = $this->createRequest();
        self::assertSame(0, $this->getCookieInt($request, 'key'));
        self::assertSame(1, $this->getCookieInt($request, 'key', default: 1));

        $request = $this->createRequest(['KEY' => 2]);
        self::assertSame(2, $this->getCookieInt($request, 'key'));
    }

    public function testGetCookieString(): void
    {
        $request = $this->createRequest();
        self::assertSame('', $this->getCookieString($request, 'key'));
        self::assertSame('default', $this->getCookieString($request, 'key', default: 'default'));

        $request = $this->createRequest(['KEY' => 'My String']);
        self::assertSame('My String', $this->getCookieString($request, 'key'));
    }

    public function testUpdateCookie(): void
    {
        $response = $this->createResponse();
        $this->updateCookie($response, 'KEY', null);
        self::assertSame('', $response->getContent());

        $response = $this->createResponse();
        $this->updateCookie($response, 'KEY', 'value');
        self::assertSame('', $response->getContent());

        $response = $this->createResponse();
        $this->updateCookie($response, 'KEY', Theme::DARK);
        self::assertSame('', $response->getContent());

        $response = $this->createResponse();
        $this->updateCookie($response, 'KEY', true);
        self::assertSame('', $response->getContent());

        $response = $this->createResponse();
        $this->updateCookie($response, 'KEY', Theme::getDefault());
        self::assertSame('', $response->getContent());
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
        $this->updateCookies($response, $values);
        self::expectNotToPerformAssertions();
    }

    #[\Override]
    protected function getCookiePath(): string
    {
        return '/';
    }

    private function createRequest(array $cookies = []): Request
    {
        foreach ($cookies as &$value) {
            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            }
        }

        return new Request(cookies: $cookies);
    }

    private function createResponse(): Response
    {
        return new Response();
    }
}
