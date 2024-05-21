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
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(AboutController::class)]
#[CoversClass(AboutLicenceController::class)]
#[CoversClass(AboutMySqlController::class)]
#[CoversClass(AboutPhpController::class)]
#[CoversClass(AboutPolicyController::class)]
#[CoversClass(AboutSymfonyController::class)]
class AboutControllerTest extends AbstractControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/about', '', Response::HTTP_FOUND];
        // redirect to login page
        yield ['/about', self::ROLE_USER];
        yield ['/about', self::ROLE_ADMIN];
        yield ['/about', self::ROLE_SUPER_ADMIN];
        yield ['/about/pdf', self::ROLE_USER];
        yield ['/about/pdf', self::ROLE_ADMIN];
        yield ['/about/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/about/word', self::ROLE_USER];
        yield ['/about/word', self::ROLE_ADMIN];
        yield ['/about/word', self::ROLE_SUPER_ADMIN];
        yield ['/about/licence'];
        yield ['/about/licence', self::ROLE_USER];
        yield ['/about/licence', self::ROLE_ADMIN];
        yield ['/about/licence', self::ROLE_SUPER_ADMIN];
        yield ['/about/licence/content', self::ROLE_USER];
        yield ['/about/licence/content', self::ROLE_ADMIN];
        yield ['/about/licence/content', self::ROLE_SUPER_ADMIN];
        yield ['/about/licence/pdf'];
        yield ['/about/licence/pdf', self::ROLE_USER];
        yield ['/about/licence/pdf', self::ROLE_ADMIN];
        yield ['/about/licence/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/about/licence/word', '', Response::HTTP_FOUND];
        // redirect to login page
        yield ['/about/licence/word', self::ROLE_USER];
        yield ['/about/licence/word', self::ROLE_ADMIN];
        yield ['/about/licence/word', self::ROLE_SUPER_ADMIN];
        yield ['/about/mysql/content', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/about/mysql/content', self::ROLE_ADMIN];
        yield ['/about/mysql/content', self::ROLE_SUPER_ADMIN];
        yield ['/about/mysql/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/about/mysql/excel', self::ROLE_ADMIN];
        yield ['/about/mysql/excel', self::ROLE_SUPER_ADMIN];
        yield ['/about/mysql/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/about/mysql/pdf', self::ROLE_ADMIN];
        yield ['/about/mysql/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/about/php/content', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/about/php/content', self::ROLE_ADMIN];
        yield ['/about/php/content', self::ROLE_SUPER_ADMIN];
        yield ['/about/php/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/about/php/excel', self::ROLE_ADMIN];
        yield ['/about/php/excel', self::ROLE_SUPER_ADMIN];
        yield ['/about/php/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/about/php/pdf', self::ROLE_ADMIN];
        yield ['/about/php/pdf', self::ROLE_SUPER_ADMIN];
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
        yield ['/about/symfony/content', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/about/symfony/content', self::ROLE_ADMIN];
        yield ['/about/symfony/content', self::ROLE_SUPER_ADMIN];
        yield ['/about/symfony/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/about/symfony/excel', self::ROLE_ADMIN];
        yield ['/about/symfony/excel', self::ROLE_SUPER_ADMIN];
        yield ['/about/symfony/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/about/symfony/pdf', self::ROLE_ADMIN];
        yield ['/about/symfony/pdf', self::ROLE_SUPER_ADMIN];
    }
}
