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

use App\Entity\User;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserControllerTest extends EntityControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/user', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user', self::ROLE_ADMIN];
        yield ['/user', self::ROLE_SUPER_ADMIN];
        yield ['/user', self::ROLE_DISABLED, Response::HTTP_FORBIDDEN];

        yield ['/user/add', self::ROLE_ADMIN];
        yield ['/user/add', self::ROLE_SUPER_ADMIN];
        yield ['/user/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];

        yield ['/user/edit/1', self::ROLE_ADMIN];
        yield ['/user/edit/1', self::ROLE_SUPER_ADMIN];

        yield ['/user/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/delete/1', self::ROLE_ADMIN];

        // can delete when connected
        yield ['/user/delete/1', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND];
        yield ['/user/delete/2', self::ROLE_SUPER_ADMIN];

        yield ['/user/show/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/show/1', self::ROLE_ADMIN];
        yield ['/user/show/1', self::ROLE_SUPER_ADMIN];

        yield ['/user/password/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/password/1', self::ROLE_ADMIN];
        yield ['/user/password/1', self::ROLE_SUPER_ADMIN];

        yield ['/user/reset', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/reset', self::ROLE_ADMIN, Response::HTTP_FOUND];
        yield ['/user/reset', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND];

        yield ['/user/reset/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/reset/1', self::ROLE_ADMIN];
        yield ['/user/reset/1', self::ROLE_SUPER_ADMIN];

        yield ['/user/rights/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/rights/1', self::ROLE_ADMIN];
        yield ['/user/rights/1', self::ROLE_SUPER_ADMIN];

        yield ['/user/rights/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/rights/pdf', self::ROLE_ADMIN];
        yield ['/user/rights/pdf', self::ROLE_SUPER_ADMIN];

        yield ['/user/rights/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/rights/excel', self::ROLE_ADMIN];
        yield ['/user/rights/excel', self::ROLE_SUPER_ADMIN];

        yield ['/user/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/pdf', self::ROLE_ADMIN];
        yield ['/user/pdf', self::ROLE_SUPER_ADMIN];

        yield ['/user/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/user/excel', self::ROLE_ADMIN];
        yield ['/user/excel', self::ROLE_SUPER_ADMIN];

        yield ['/user/parameters', self::ROLE_USER];
        yield ['/user/parameters', self::ROLE_ADMIN];
        yield ['/user/parameters', self::ROLE_SUPER_ADMIN];
    }

    public function testMessageSuccess(): void
    {
        $uri = \sprintf('/user/message/%d', self::ID_USER);
        $data = ['user_comment[message]' => 'The message to send.'];
        $this->checkForm($uri, 'common.button_send', $data);
    }

    public function testMessageToSameUser(): void
    {
        $uri = \sprintf('/user/message/%d', self::ID_ADMIN);
        $this->loginUsername(self::ROLE_ADMIN);
        $this->client->request(Request::METHOD_GET, $uri);
        $this->checkResponse($uri, self::ROLE_ADMIN, Response::HTTP_FOUND);
    }

    public function testPassword(): void
    {
        $uri = \sprintf('/user/password/%d', self::ID_ADMIN);
        $data = [
            'plainPassword[first]' => '$password123456#',
            'plainPassword[second]' => '$password123456#',
        ];
        $this->checkForm($uri, data: $data);
    }

    public function testResetAllPasswordRequestNoUser(): void
    {
        $this->loginUsername(self::ROLE_SUPER_ADMIN);
        $this->client->request(Request::METHOD_POST, '/user/reset');
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    /**
     * @throws ORMException
     */
    public function testResetAllPasswordRequestOneUser(): void
    {
        $this->setResetPasswordRequest(self::ROLE_USER);
        $this->loginUsername(self::ROLE_SUPER_ADMIN);
        $this->client->request(Request::METHOD_POST, '/user/reset');
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    /**
     * @throws ORMException
     */
    public function testResetAllPasswordSuccess(): void
    {
        $this->setResetPasswordRequest(self::ROLE_USER);
        $this->setResetPasswordRequest(self::ROLE_ADMIN);
        $this->checkForm('/user/reset', 'common.button_delete');
    }

    public function testResetNotResettable(): void
    {
        $uri = \sprintf('/user/reset/%d', self::ID_ADMIN);
        $this->checkForm($uri, 'common.button_delete');
    }

    /**
     * @throws ORMException
     */
    public function testResetSuccess(): void
    {
        $user = $this->setResetPasswordRequest(self::ROLE_USER);
        $uri = \sprintf('/user/reset/%d', (int) $user->getId());
        $this->checkForm($uri, 'common.button_delete');
    }

    public function testRightsNoChange(): void
    {
        $this->loginUsername(self::ROLE_SUPER_ADMIN);
        $uri = \sprintf('/user/rights/%d', self::ID_USER);
        $this->client->request(Request::METHOD_POST, $uri);
        $name = $this->getService(TranslatorInterface::class)
            ->trans('common.button_ok');
        $this->client->submitForm($name);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testRightsSameUser(): void
    {
        $this->loginUsername(self::ROLE_ADMIN);
        $uri = \sprintf('/user/rights/%d', self::ID_ADMIN);
        $this->client->request(Request::METHOD_POST, $uri);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testRightsWithChanges(): void
    {
        $this->loginUsername(self::ROLE_SUPER_ADMIN);
        $data = ['user_rights[overwrite]' => 1];
        $uri = \sprintf('/user/rights/%d', self::ID_USER);
        $this->client->request(Request::METHOD_POST, $uri);
        $name = $this->getService(TranslatorInterface::class)
            ->trans('common.button_ok');
        $this->client->submitForm($name, $data);
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    /**
     * @throws ORMException
     */
    private function setResetPasswordRequest(string $username): User
    {
        $user = $this->loadUser($username);
        $expiresAt = \DateTimeImmutable::createFromInterface(new \DateTime());
        $user->setResetPasswordRequest($expiresAt, 'selector', 'hashedToken');
        $this->addEntity($user);

        return $user;
    }
}
