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

use App\Controller\AboutController;
use App\Controller\AboutLicenceController;
use App\Controller\AboutMySqlController;
use App\Controller\AboutPhpController;
use App\Controller\AboutPolicyController;
use App\Controller\AboutSymfonyController;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(AboutController::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(AboutLicenceController::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(AboutMySqlController::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(AboutPhpController::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(AboutPolicyController::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(AboutSymfonyController::class)]
class AboutControllerTest extends AbstractControllerTestCase
{
    public static function getRoutes(): array
    {
        return [
            ['/about', '', Response::HTTP_FOUND], // redirect to login page
            ['/about', self::ROLE_USER],
            ['/about', self::ROLE_ADMIN],
            ['/about', self::ROLE_SUPER_ADMIN],

            ['/about/pdf', self::ROLE_USER],
            ['/about/pdf', self::ROLE_ADMIN],
            ['/about/pdf', self::ROLE_SUPER_ADMIN],

            ['/about/word', self::ROLE_USER],
            ['/about/word', self::ROLE_ADMIN],
            ['/about/word', self::ROLE_SUPER_ADMIN],

            ['/about/licence'],
            ['/about/licence', self::ROLE_USER],
            ['/about/licence', self::ROLE_ADMIN],
            ['/about/licence', self::ROLE_SUPER_ADMIN],

            ['/about/licence/content', self::ROLE_USER],
            ['/about/licence/content', self::ROLE_ADMIN],
            ['/about/licence/content', self::ROLE_SUPER_ADMIN],

            ['/about/licence/pdf'],
            ['/about/licence/pdf', self::ROLE_USER],
            ['/about/licence/pdf', self::ROLE_ADMIN],
            ['/about/licence/pdf', self::ROLE_SUPER_ADMIN],

            ['/about/licence/word', '', Response::HTTP_FOUND], // redirect to login page
            ['/about/licence/word', self::ROLE_USER],
            ['/about/licence/word', self::ROLE_ADMIN],
            ['/about/licence/word', self::ROLE_SUPER_ADMIN],

            ['/about/mysql/content', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/about/mysql/content', self::ROLE_ADMIN],
            ['/about/mysql/content', self::ROLE_SUPER_ADMIN],

            ['/about/mysql/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/about/mysql/excel', self::ROLE_ADMIN],
            ['/about/mysql/excel', self::ROLE_SUPER_ADMIN],

            ['/about/mysql/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/about/mysql/pdf', self::ROLE_ADMIN],
            ['/about/mysql/pdf', self::ROLE_SUPER_ADMIN],

            ['/about/php/content', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/about/php/content', self::ROLE_ADMIN],
            ['/about/php/content', self::ROLE_SUPER_ADMIN],

            ['/about/php/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/about/php/excel', self::ROLE_ADMIN],
            ['/about/php/excel', self::ROLE_SUPER_ADMIN],

            ['/about/php/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/about/php/pdf', self::ROLE_ADMIN],
            ['/about/php/pdf', self::ROLE_SUPER_ADMIN],

            ['/about/policy'],
            ['/about/policy', self::ROLE_USER],
            ['/about/policy', self::ROLE_ADMIN],
            ['/about/policy', self::ROLE_SUPER_ADMIN],

            ['/about/policy/content', self::ROLE_USER],
            ['/about/policy/content', self::ROLE_ADMIN],
            ['/about/policy/content', self::ROLE_SUPER_ADMIN],

            ['/about/policy/pdf'],
            ['/about/policy/pdf', self::ROLE_USER],
            ['/about/policy/pdf', self::ROLE_ADMIN],
            ['/about/policy/pdf', self::ROLE_SUPER_ADMIN],

            ['/about/policy/word', '', Response::HTTP_FOUND], // redirect to login page
            ['/about/policy/word', self::ROLE_USER],
            ['/about/policy/word', self::ROLE_ADMIN],
            ['/about/policy/word', self::ROLE_SUPER_ADMIN],

            ['/about/symfony/content', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/about/symfony/content', self::ROLE_ADMIN],
            ['/about/symfony/content', self::ROLE_SUPER_ADMIN],

            ['/about/symfony/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/about/symfony/excel', self::ROLE_ADMIN],
            ['/about/symfony/excel', self::ROLE_SUPER_ADMIN],

            ['/about/symfony/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/about/symfony/pdf', self::ROLE_ADMIN],
            ['/about/symfony/pdf', self::ROLE_SUPER_ADMIN],
        ];
    }
}
