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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SymfonyCasts\Bundle\ResetPassword\Exception\FakeRepositoryException;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

final class ResetPasswordControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/reset-password', self::ROLE_USER];
        yield ['/reset-password', self::ROLE_ADMIN];
        yield ['/reset-password', self::ROLE_SUPER_ADMIN];

        yield ['/reset-password/check-email', self::ROLE_USER];
        yield ['/reset-password/check-email', self::ROLE_ADMIN];
        yield ['/reset-password/check-email', self::ROLE_SUPER_ADMIN];

        yield ['/reset-password/reset/', self::ROLE_USER, Response::HTTP_MOVED_PERMANENTLY];
        yield ['/reset-password/reset/', self::ROLE_ADMIN, Response::HTTP_MOVED_PERMANENTLY];
        yield ['/reset-password/reset/', self::ROLE_SUPER_ADMIN, Response::HTTP_MOVED_PERMANENTLY];

        yield ['/reset-password/reset/fake', '', Response::HTTP_FOUND];
        yield ['/reset-password/reset/fake', self::ROLE_USER, Response::HTTP_FOUND];
        yield ['/reset-password/reset/fake', self::ROLE_ADMIN, Response::HTTP_FOUND];
        yield ['/reset-password/reset/fake', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND];
    }

    public function testRequestForm(): void
    {
        $data = ['user' => self::ROLE_SUPER_ADMIN];
        $this->setResetPasswordPublicToken();
        $this->checkForm(
            uri: '/reset-password',
            id: 'resetting.request.submit',
            data: $data,
        );
    }

    public function testResetTokenWithException(): void
    {
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->method('validateTokenAndFetchUser')
            ->willThrowException(new FakeRepositoryException());
        $this->setService(ResetPasswordHelperInterface::class, $helper);
        $this->setResetPasswordPublicToken();

        $url = '/reset-password/reset';
        $this->client->request(
            method: Request::METHOD_GET,
            uri: $url
        );
        $this->checkResponse($url, self::ROLE_USER, Response::HTTP_FOUND);
    }

    public function testResetWithToken(): void
    {
        $user = $this->loadUser(self::ROLE_SUPER_ADMIN);
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->method('validateTokenAndFetchUser')
            ->willReturn($user);
        $this->setService(ResetPasswordHelperInterface::class, $helper);
        $this->setResetPasswordPublicToken();

        $data = [
            'plainPassword[first]' => '$A722-32012d313e5c',
            'plainPassword[second]' => '$A722-32012d313e5c',
        ];

        $this->client->request(
            Request::METHOD_POST,
            '/reset-password/reset',
            $data
        );
        $this->checkResponse('/reset-password/reset', self::ROLE_USER, Response::HTTP_OK);
    }

    public function testResetWithTokenNull(): void
    {
        $url = '/reset-password/reset';
        $this->client->request(
            method: Request::METHOD_GET,
            uri: $url
        );
        $this->checkResponse($url, self::ROLE_USER, Response::HTTP_NOT_FOUND);
    }

    private function setResetPasswordPublicToken(): void
    {
        $session = $this->client->getSession();
        $session?->set('ResetPasswordPublicToken', 'fake');
        $session?->save();
    }
}
