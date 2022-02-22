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

/**
 * Unit test for {@link App\Controller\AbstractController} class.
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

            ['/about/licence', self::ROLE_USER],
            ['/about/licence', self::ROLE_ADMIN],
            ['/about/licence', self::ROLE_SUPER_ADMIN],

            ['/about/licence/pdf', self::ROLE_USER],
            ['/about/licence/pdf', self::ROLE_ADMIN],
            ['/about/licence/pdf', self::ROLE_SUPER_ADMIN],

            ['/about/php/ini', self::ROLE_USER],
            ['/about/php/ini', self::ROLE_ADMIN],
            ['/about/php/ini', self::ROLE_SUPER_ADMIN],

            ['/about/policy', self::ROLE_USER],
            ['/about/policy', self::ROLE_ADMIN],
            ['/about/policy', self::ROLE_SUPER_ADMIN],

            ['/about/policy/pdf', self::ROLE_USER],
            ['/about/policy/pdf', self::ROLE_ADMIN],
            ['/about/policy/pdf', self::ROLE_SUPER_ADMIN],
        ];
    }
}
