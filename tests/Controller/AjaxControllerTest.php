<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Tests\Web\AbstractAuthenticateWebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Unit test for {@link App\Controller\AjaxController} class.
 *
 * @author Laurent Muller
 */
class AjaxControllerTest extends AbstractAuthenticateWebTestCase
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * {@inheritDoc}
     *
     * @see \App\Tests\Web\AbstractAuthenticateWebTestCase::setUp()
     */
    protected function setUp(): void
    {
        parent::setUp();

        $translator = $this->getContainer()->get(TranslatorInterface::class);
        if ($translator instanceof TranslatorInterface) {
            $this->translator = $translator;
        }
    }

    public function getUserEmails(): array
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

    public function getUserNames(): array
    {
        return [
            [true, 'myemail_fake_zz'],
            [true, 'ROLE_SUPER_ADMIN', 1],
            ['username.blank'],
            ['username.short', 'A'],
            ['username.long', \str_repeat('A', 200)],
            ['username.already_used', 'ROLE_SUPER_ADMIN'],
        ];
    }

    public function getUsers(): array
    {
        return [
            [true, 'ROLE_SUPER_ADMIN'],
            [true, 'ROLE_SUPER_ADMIN@TEST.COM'],
            ['username.not_found', 'USER_XXX_INVALID'],
            ['username.not_found', 'USER_XXX_INVALID@INVALID.COM'],
        ];
    }

    /**
     * @param string|bool $expected
     *
     * @dataProvider getUsers
     */
    public function testCheckUser($expected, string $user = null): void
    {
        $parameters = ['user' => $user];
        $this->client->request('GET', '/ajax/checkuser', $parameters);
        $response = $this->client->getResponse();
        $this->validateRespons($response, $expected);
    }

    /**
     * @param string|bool $expected
     *
     * @dataProvider getUserEmails
     */
    public function testCheckUserEmail($expected, string $email = null, int $id = null): void
    {
        $this->loginUserName('ROLE_SUPER_ADMIN');
        $parameters = ['email' => $email, 'id' => $id];
        $this->client->request('GET', '/ajax/checkuseremail', $parameters);
        $response = $this->client->getResponse();
        $this->validateRespons($response, $expected);
    }

    /**
     * @param string|bool $expected
     *
     * @dataProvider getUserNames
     */
    public function testCheckUserName($expected, string $username = null, int $id = null): void
    {
        $this->loginUserName('ROLE_SUPER_ADMIN');
        $parameters = ['username' => $username, 'id' => $id];
        $this->client->request('GET', '/ajax/checkusername', $parameters);
        $response = $this->client->getResponse();
        $this->validateRespons($response, $expected);
    }

    /**
     * @param string|bool $expected
     */
    private function validateRespons(Response $response, $expected): void
    {
        $this->assertTrue($response->isOk());

        $result = \json_decode((string) $response->getContent(), true);
        if (\is_string($expected)) {
            $expected = $this->translator->trans($expected, [], 'validators');
        }
        $this->assertSame($expected, $result);
    }
}
