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

use App\Controller\AdminController;
use App\Interfaces\PropertyServiceInterface;
use App\Service\DictionaryService;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(AdminController::class)]
class AdminControllerTest extends ControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/admin/clear', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/admin/clear', self::ROLE_ADMIN];
        yield ['/admin/clear', self::ROLE_SUPER_ADMIN];

        yield ['/admin/parameters', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/admin/parameters', self::ROLE_ADMIN];
        yield ['/admin/parameters', self::ROLE_SUPER_ADMIN];

        yield ['/admin/rights/admin', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/admin/rights/admin', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
        yield ['/admin/rights/admin', self::ROLE_SUPER_ADMIN];

        yield ['/admin/rights/user', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/admin/rights/user', self::ROLE_ADMIN];
        yield ['/admin/rights/user', self::ROLE_SUPER_ADMIN];
    }

    public function testClearCache(): void
    {
        $this->checkForm(
            'admin/clear',
            'clear_cache.submit',
            [],
            self::ROLE_SUPER_ADMIN
        );
    }

    public function testParametersNoChange(): void
    {
        $this->checkForm(
            'admin/parameters',
            'common.button_ok',
            [],
            self::ROLE_SUPER_ADMIN
        );
    }

    public function testParametersWithChanges(): void
    {
        $name = $this->getService(DictionaryService::class)
            ->getRandomWord();
        $data = [PropertyServiceInterface::P_CUSTOMER_NAME => $name];
        $this->checkForm(
            'admin/parameters',
            'common.button_ok',
            $data,
            self::ROLE_SUPER_ADMIN
        );
    }

    public function testRightAdmin(): void
    {
        $this->checkForm(
            'admin/rights/admin',
            'common.button_ok',
            [],
            self::ROLE_SUPER_ADMIN
        );
    }

    public function testRightUser(): void
    {
        $this->checkForm('admin/rights/user');
    }
}
