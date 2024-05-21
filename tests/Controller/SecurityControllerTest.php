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

use App\Controller\SecurityController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;

#[CoversClass(SecurityController::class)]
class SecurityControllerTest extends AbstractControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/login', AuthenticatedVoter::PUBLIC_ACCESS];
        yield ['/login', self::ROLE_USER, Response::HTTP_FOUND];
        yield ['/login', self::ROLE_ADMIN, Response::HTTP_FOUND];
        yield ['/login', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND];
    }
}
