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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(AjaxUserController::class)]
class AjaxUserControllerTest extends AbstractAuthenticateWebTestCase
{
    private ?TranslatorInterface $translator = null;

    public static function getEmails(): \Iterator
    {
        yield [true, 'myemail_fake_zz@myemail.com'];
        yield [true, 'ROLE_SUPER_ADMIN@TEST.COM', 1];
        yield ['email.blank'];
        yield ['email.short', 'A'];
        yield ['email.long', \str_repeat('A', 200)];
        yield ['email.already_used', 'ROLE_SUPER_ADMIN@TEST.COM'];
    }

    public static function getNames(): \Iterator
    {
        yield [true, 'myEmail_fake_zz'];
        yield [true, 'ROLE_SUPER_ADMIN', 1];
        yield ['username.blank'];
        yield ['username.short', 'A'];
        yield ['username.long', \str_repeat('A', 200)];
        yield ['username.already_used', 'ROLE_SUPER_ADMIN'];
    }

    public static function getUsers(): \Iterator
    {
        yield ['username.blank'];
        yield [true, 'ROLE_SUPER_ADMIN'];
        yield [true, 'ROLE_SUPER_ADMIN@TEST.COM'];
        yield ['username.not_found', 'USER_XXX_INVALID'];
        yield ['username.not_found', 'USER_XXX_INVALID@INVALID.COM'];
    }

    #[DataProvider('getEmails')]
    public function testCheckEmail(string|bool $expected, ?string $email = null, ?int $id = null): void
    {
        $this->loginUsername('ROLE_SUPER_ADMIN');
        $parameters = ['email' => $email, 'id' => $id];
        $this->client->request(Request::METHOD_GET, '/ajax/check/user/email', $parameters);
        $response = $this->client->getResponse();
        $this->validateResponse($response, $expected);
    }

    #[DataProvider('getNames')]
    public function testCheckName(string|bool $expected, ?string $username = null, ?int $id = null): void
    {
        $this->loginUsername('ROLE_SUPER_ADMIN');
        $parameters = ['username' => $username, 'id' => $id];
        $this->client->request(Request::METHOD_GET, '/ajax/check/user/name', $parameters);
        $response = $this->client->getResponse();
        $this->validateResponse($response, $expected);
    }

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
        } catch (\JsonException $e) {
            self::fail($e->getMessage());
        }
    }
}
