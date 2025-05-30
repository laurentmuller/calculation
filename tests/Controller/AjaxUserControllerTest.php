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

use App\Tests\Web\AuthenticateWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class AjaxUserControllerTest extends AuthenticateWebTestCase
{
    private ?TranslatorInterface $translator = null;

    /**
     * @phpstan-return \Generator<int, array{0: bool|non-empty-string, 1?: string, 2?: 1}>
     */
    public static function getEmails(): \Generator
    {
        yield [true, 'myemail_fake_zz@myemail.com'];
        yield [true, 'ROLE_SUPER_ADMIN@TEST.COM', 1];
        yield ['email.blank'];
        yield ['email.short', 'A'];
        yield ['email.long', \str_repeat('A', 200)];
        yield ['email.already_used', 'ROLE_SUPER_ADMIN@TEST.COM'];
    }

    /**
     * @phpstan-return \Generator<int, array{0: bool|string, 1?: string, 2?: 1}>
     */
    public static function getNames(): \Generator
    {
        yield [true, 'myEmail_fake_zz'];
        yield [true, 'ROLE_SUPER_ADMIN', 1];
        yield ['username.blank'];
        yield ['username.short', 'A'];
        yield ['username.long', \str_repeat('A', 200)];
        yield ['username.already_used', 'ROLE_SUPER_ADMIN'];
    }

    /**
     * @phpstan-return \Generator<int, array{0: bool|string, 1?: string}>
     */
    public static function getUsers(): \Generator
    {
        yield ['username.blank'];
        yield [true, 'ROLE_SUPER_ADMIN'];
        yield [true, 'ROLE_SUPER_ADMIN@TEST.COM'];
        yield ['username.not_found', 'USER_XXX_INVALID'];
        yield ['username.not_found', 'USER_XXX_INVALID@INVALID.COM'];
    }

    /**
     * @throws \JsonException
     */
    #[DataProvider('getEmails')]
    public function testCheckEmail(string|bool $expected, ?string $email = null, ?int $id = null): void
    {
        $this->loginUsername('ROLE_SUPER_ADMIN');
        $parameters = ['email' => $email, 'id' => $id];
        $this->client->request(Request::METHOD_GET, '/ajax/check/user/email', $parameters);
        $response = $this->client->getResponse();
        $this->validateResponse($response, $expected);
    }

    /**
     * @throws \JsonException
     */
    #[DataProvider('getNames')]
    public function testCheckName(string|bool $expected, ?string $username = null, ?int $id = null): void
    {
        $this->loginUsername('ROLE_SUPER_ADMIN');
        $parameters = ['username' => $username, 'id' => $id];
        $this->client->request(Request::METHOD_GET, '/ajax/check/user/name', $parameters);
        $response = $this->client->getResponse();
        $this->validateResponse($response, $expected);
    }

    /**
     * @throws \JsonException
     */
    #[DataProvider('getUsers')]
    public function testCheckUser(string|bool $expected, ?string $user = null): void
    {
        $parameters = ['user' => $user];
        $this->client->request(Request::METHOD_GET, '/ajax/check/user', $parameters);
        $response = $this->client->getResponse();
        $this->validateResponse($response, $expected);
    }

    private function getTranslator(): TranslatorInterface
    {
        if (!$this->translator instanceof TranslatorInterface) {
            $this->translator = $this->getService(TranslatorInterface::class);
        }

        return $this->translator;
    }

    /**
     * @throws \JsonException
     */
    private function validateResponse(Response $response, string|bool $expected): void
    {
        self::assertTrue($response->isOk());
        $content = $response->getContent();
        self::assertIsString($content);
        $result = \json_decode(json: $content, flags: \JSON_THROW_ON_ERROR);
        if (\is_string($expected)) {
            $expected = $this->getTranslator()->trans(id: $expected, domain: 'validators');
        }
        self::assertSame($expected, $result);
    }
}
