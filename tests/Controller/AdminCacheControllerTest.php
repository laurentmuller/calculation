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

use App\Service\CacheService;
use App\Tests\Controller\ControllerTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AdminCacheControllerTest extends ControllerTestCase
{
    #[Override]
    public static function getRoutes(): Generator
    {
        yield ['/admin/clear', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/admin/clear', self::ROLE_ADMIN];
        yield ['/admin/clear', self::ROLE_SUPER_ADMIN];
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
        $service->method('list')
            ->willReturn(['cache' => ['app']]);
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

    public function testClearCacheThrowException(): void
    {
        $service = $this->createMock(CacheService::class);
        $service->method('list')
            ->willReturn(['cache' => ['app']]);
        $service->method('clear')
            ->willThrowException(new Exception('Fake Message'));
        $this->setService(CacheService::class, $service);

        $this->checkForm(
            uri: 'admin/clear',
            id: 'clear_cache.submit',
            userName: self::ROLE_SUPER_ADMIN,
            followRedirect: false,
            disableReboot: true
        );
    }

    public function testListCacheThrowException(): void
    {
        $service = $this->createMock(CacheService::class);
        $service->method('list')
            ->willThrowException(new Exception('Fake Message'));
        $this->setService(CacheService::class, $service);

        $this->checkRoute(
            url: 'admin/clear',
            username: self::ROLE_SUPER_ADMIN,
            expected: Response::HTTP_FOUND,
            method: Request::METHOD_POST
        );
    }
}
