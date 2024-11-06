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

use Symfony\Component\HttpFoundation\Response;

class AboutPolicyControllerTest extends ControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/about/policy'];
        yield ['/about/policy', self::ROLE_USER];
        yield ['/about/policy', self::ROLE_ADMIN];
        yield ['/about/policy', self::ROLE_SUPER_ADMIN];
        yield ['/about/policy/content', self::ROLE_USER];
        yield ['/about/policy/content', self::ROLE_ADMIN];
        yield ['/about/policy/content', self::ROLE_SUPER_ADMIN];
        yield ['/about/policy/pdf'];
        yield ['/about/policy/pdf', self::ROLE_USER];
        yield ['/about/policy/pdf', self::ROLE_ADMIN];
        yield ['/about/policy/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/about/policy/word', '', Response::HTTP_FOUND];
        // redirect to login page
        yield ['/about/policy/word', self::ROLE_USER];
        yield ['/about/policy/word', self::ROLE_ADMIN];
        yield ['/about/policy/word', self::ROLE_SUPER_ADMIN];
    }
}
