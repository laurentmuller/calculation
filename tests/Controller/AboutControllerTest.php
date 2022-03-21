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

use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for {@link App\Controller\AboutController} class.
 *
 * @author Laurent Muller
 */
class AboutControllerTest extends AbstractControllerTest
{
    public function getRoutes(): array
    {
        return [
            ['/about', self::ROLE_USER],
            ['/about', self::ROLE_ADMIN],
            ['/about', self::ROLE_SUPER_ADMIN],

            ['/about/pdf', self::ROLE_USER],
            ['/about/pdf', self::ROLE_ADMIN],
            ['/about/pdf', self::ROLE_SUPER_ADMIN],

            ['/about/licence', ''],
            ['/about/licence', self::ROLE_USER],
            ['/about/licence', self::ROLE_ADMIN],
            ['/about/licence', self::ROLE_SUPER_ADMIN],

            ['/about/licence/content', self::ROLE_USER],
            ['/about/licence/content', self::ROLE_ADMIN],
            ['/about/licence/content', self::ROLE_SUPER_ADMIN],

            ['/about/licence/pdf', ''],
            ['/about/licence/pdf', self::ROLE_USER],
            ['/about/licence/pdf', self::ROLE_ADMIN],
            ['/about/licence/pdf', self::ROLE_SUPER_ADMIN],

            ['/about/mysql/content', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/about/mysql/content', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/about/mysql/content', self::ROLE_SUPER_ADMIN],

            ['/about/php/content', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/about/php/content', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/about/php/content', self::ROLE_SUPER_ADMIN],

            // To be checked why array is empty
            // ['/about/php/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            // ['/about/php/pdf', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            // ['/about/php/pdf', self::ROLE_SUPER_ADMIN],

            ['/about/policy', ''],
            ['/about/policy', self::ROLE_USER],
            ['/about/policy', self::ROLE_ADMIN],
            ['/about/policy', self::ROLE_SUPER_ADMIN],

            ['/about/policy/content', self::ROLE_USER],
            ['/about/policy/content', self::ROLE_ADMIN],
            ['/about/policy/content', self::ROLE_SUPER_ADMIN],

            ['/about/policy/pdf', ''],
            ['/about/policy/pdf', self::ROLE_USER],
            ['/about/policy/pdf', self::ROLE_ADMIN],
            ['/about/policy/pdf', self::ROLE_SUPER_ADMIN],

            ['/about/symfony/content', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/about/symfony/content', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/about/symfony/content', self::ROLE_SUPER_ADMIN],
        ];
    }
}
