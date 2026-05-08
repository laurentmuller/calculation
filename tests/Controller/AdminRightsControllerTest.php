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

use App\Enums\EntityPermission;
use App\Parameter\ApplicationParameters;
use App\Tests\Controller\ControllerTestCase;
use Symfony\Component\HttpFoundation\Response;

final class AdminRightsControllerTest extends ControllerTestCase
{
    #[Override]
    public static function getRoutes(): Generator
    {
        yield ['/admin/rights/admin', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/admin/rights/admin', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
        yield ['/admin/rights/admin', self::ROLE_SUPER_ADMIN];

        yield ['/admin/rights/user', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/admin/rights/user', self::ROLE_ADMIN];
        yield ['/admin/rights/user', self::ROLE_SUPER_ADMIN];
    }

    public function testRightAdmin(): void
    {
        $this->checkForm(
            uri: 'admin/rights/admin',
            userName: self::ROLE_SUPER_ADMIN
        );
    }

    public function testRightUser(): void
    {
        $this->checkForm('admin/rights/user');
    }

    public function testRightUserNoChange(): void
    {
        $service = self::getService(ApplicationParameters::class);
        $service->getRights()->setUserRights(null);
        $service->save();

        $values = $this->getPermissionValues();
        $values[0] = $values[1] = $values[5] = true;
        $this->checkForm(
            uri: 'admin/rights/user',
            data: ['role_rights[rights][GlobalMargin]' => $values]
        );
    }

    public function testRightUserWithChanges(): void
    {
        $values = $this->getPermissionValues();
        $this->checkForm(
            uri: 'admin/rights/user',
            data: ['role_rights[rights][GlobalMargin]' => $values]
        );
    }

    private function getPermissionValues(): array
    {
        return \array_fill(0, \count(EntityPermission::cases()), false);
    }
}
