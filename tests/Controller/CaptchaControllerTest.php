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

use App\Controller\CaptchaController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;

#[\PHPUnit\Framework\Attributes\CoversClass(CaptchaController::class)]
class CaptchaControllerTest extends AbstractControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/captcha/image', AuthenticatedVoter::PUBLIC_ACCESS, Response::HTTP_OK,  Request::METHOD_GET, true];
        yield ['/captcha/image', self::ROLE_USER];
        yield ['/captcha/image', self::ROLE_ADMIN];
        yield ['/captcha/image', self::ROLE_SUPER_ADMIN];

        yield ['/captcha/validate', AuthenticatedVoter::PUBLIC_ACCESS, Response::HTTP_OK,  Request::METHOD_GET, true];
        yield ['/captcha/validate', self::ROLE_USER];
        yield ['/captcha/validate', self::ROLE_ADMIN];
        yield ['/captcha/validate', self::ROLE_SUPER_ADMIN];
    }
}
