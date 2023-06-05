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

use App\Controller\AjaxUserController;
use App\Tests\Web\AbstractAuthenticateWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(AjaxUserController::class)]
class AjaxUserControllerTest extends AbstractAuthenticateWebTestCase
{
    private ?TranslatorInterface $translator = null;

    /**
     * @return array<array{0: string|bool, 1?: string|null, 2?: int|null}>
     */
    public static function getUserEmails(): array
    {
        return [
            [true, 'myemail_fake_zz@myemail.com'],
            [true, 'ROLE_SUPER_ADMIN@TEST.COM', 1],
            ['email.blank'],
            ['email.short', 'A'],
            ['email.long', \str_repeat('A', 200)],
            ['email.already_used', 'ROLE_SUPER_ADMIN@TEST.COM'],
        ];
    }

    /**
     * @return array<array{0: string|bool, 1?: string, 2?: int}>
     */
    public static function getUserNames(): array
    {
        return [
            [true, 'myEmail_fake_zz'],
            [true, 'ROLE_SUPER_ADMIN', 1],
            ['username.blank'],
            ['username.short', 'A'],
            ['username.long', \str_repeat('A', 200)],
            ['username.already_used', 'ROLE_SUPER_ADMIN'],
        ];
    }

    /**
     * @return array<array{0: bool|string, 1: string}>
     */
    public static function getUsers(): array
    {
        return [
            [true, 'ROLE_SUPER_ADMIN'],
            [true, 'ROLE_SUPER_ADMIN@TEST.COM'],
            ['username.not_found', 'USER_XXX_INVALID'],
            ['username.not_found', 'USER_XXX_INVALID@INVALID.COM'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getUsers')]
    public function testCheckUser(string|bool $expected, string $user = null): void
    {
        $parameters = ['user' => $user];
        self::assertNotNull($this->client);
        $this->client->request(Request::METHOD_GET, '/ajax/check/user', $parameters);
        $response = $this->client->getResponse();
        $this->validateResponse($response, $expected);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getUserEmails')]
    public function testCheckUserEmail(string|bool $expected, string $email = null, int $id = null): void
    {
        $this->loginUsername('ROLE_SUPER_ADMIN');
        $parameters = ['email' => $email, 'id' => $id];
        self::assertNotNull($this->client);
        $this->client->request(Request::METHOD_GET, '/ajax/check/user/email', $parameters);
        $response = $this->client->getResponse();
        $this->validateResponse($response, $expected);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getUserNames')]
    public function testCheckUserName(string|bool $expected, string $username = null, int $id = null): void
    {
        $this->loginUsername('ROLE_SUPER_ADMIN');
        $parameters = ['username' => $username, 'id' => $id];
        self::assertNotNull($this->client);
        $this->client->request(Request::METHOD_GET, '/ajax/check/user/name', $parameters);
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

    private function validateResponse(Response $response, string|bool $expected): void
    {
        self::assertTrue($response->isOk());

        try {
            $content = $response->getContent();
            self::assertIsString($content);
            $result = \json_decode(json: $content, flags: \JSON_THROW_ON_ERROR);
            if (\is_string($expected)) {
                $expected = $this->getTranslator()->trans(id: $expected, domain: 'validators');
            }
            self::assertSame($expected, $result);
        } catch (\UnexpectedValueException|\JsonException $e) {
            self::fail($e->getMessage());
        }
    }
}
