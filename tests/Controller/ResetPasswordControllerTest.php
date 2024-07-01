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

use App\Controller\ResetPasswordController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(ResetPasswordController::class)]
class ResetPasswordControllerTest extends ControllerTestCase
{
    public static function getRoutes(): \Iterator
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
        yield ['/reset-password/reset/fake', self::ROLE_USER, Response::HTTP_FOUND];
        yield ['/reset-password/reset/fake', self::ROLE_ADMIN, Response::HTTP_FOUND];
        yield ['/reset-password/reset/fake', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND];
    }

    public function testRequest(): void
    {
        $data = ['user' => self::ROLE_USER];
        $this->checkForm(
            '/reset-password',
            'resetting.request.submit',
            $data,
            self::ROLE_USER
        );
    }

    public function testResetWithTokenNull(): void
    {
        $url = '/reset-password/reset';
        $this->client->request(Request::METHOD_GET, $url);
        $this->checkResponse($url, self::ROLE_USER, Response::HTTP_NOT_FOUND);
    }
}
