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

use App\Enums\EntityPermission;
use App\Parameter\ApplicationParameters;
use App\Service\CacheService;
use App\Service\DictionaryService;
use Symfony\Component\HttpFoundation\Response;

final class AdminControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/admin/clear', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/admin/clear', self::ROLE_ADMIN];
        yield ['/admin/clear', self::ROLE_SUPER_ADMIN];

        yield ['/admin/dump-sql', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/admin/dump-sql', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
        yield ['/admin/dump-sql', self::ROLE_SUPER_ADMIN];

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
            uri: 'admin/clear',
            id: 'clear_cache.submit',
            userName: self::ROLE_SUPER_ADMIN
        );
    }

    public function testClearCacheFalse(): void
    {
        $service = $this->createMock(CacheService::class);
        $service->method('clear')
            ->willReturn(false);
        $this->setService(CacheService::class, $service);

        $this->checkForm(
            uri: 'admin/clear',
            id: 'clear_cache.submit',
            userName: self::ROLE_SUPER_ADMIN,
            disableReboot: true
        );
    }

    public function testClearCachePoolThrowException(): void
    {
        $service = $this->createMock(CacheService::class);
        $service->method('list')
            ->willThrowException(new \Exception('Fake Message'));
        $this->setService(CacheService::class, $service);

        $this->checkForm(
            uri: 'admin/clear',
            id: 'clear_cache.submit',
            userName: self::ROLE_SUPER_ADMIN,
            disableReboot: true
        );
    }

    public function testClearCacheThrowException(): void
    {
        $service = $this->createMock(CacheService::class);
        $service->method('clear')
            ->willThrowException(new \Exception('Fake Message'));
        $this->setService(CacheService::class, $service);

        $this->checkForm(
            uri: 'admin/clear',
            id: 'clear_cache.submit',
            userName: self::ROLE_SUPER_ADMIN,
            followRedirect: false,
            disableReboot: true
        );
    }

    public function testParametersNoChange(): void
    {
        $this->checkForm(
            uri: 'admin/parameters',
            userName: self::ROLE_SUPER_ADMIN
        );
    }

    public function testParametersWithChanges(): void
    {
        $name = $this->getService(DictionaryService::class)
            ->getRandomWord();
        $data = ['customer[name]' => $name];
        $this->checkForm(
            uri: 'admin/parameters',
            data: $data,
            userName: self::ROLE_SUPER_ADMIN
        );
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
