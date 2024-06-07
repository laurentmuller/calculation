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

use App\Controller\LogController;
use App\Tests\ContainerServiceTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(LogController::class)]
class LogControllerTest extends AbstractControllerTestCase
{
    use ContainerServiceTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $logger = $this->getService(LoggerInterface::class);
        $logger->info('LogControllerTest: A message for testing purposes.');
    }

    public static function getRoutes(): \Iterator
    {
        yield ['/log', self::ROLE_DISABLED, Response::HTTP_FORBIDDEN];
        yield ['/log', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/log', self::ROLE_ADMIN];
        yield ['/log', self::ROLE_SUPER_ADMIN];
        yield ['/log/delete', self::ROLE_DISABLED, Response::HTTP_FORBIDDEN];
        yield ['/log/delete', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/log/delete', self::ROLE_ADMIN];
        yield ['/log/delete', self::ROLE_SUPER_ADMIN];
        yield ['/log/download', self::ROLE_DISABLED, Response::HTTP_FORBIDDEN];
        yield ['/log/download', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/log/download', self::ROLE_ADMIN];
        yield ['/log/download', self::ROLE_SUPER_ADMIN];
        yield ['/log/excel', self::ROLE_DISABLED, Response::HTTP_FORBIDDEN];
        yield ['/log/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/log/excel', self::ROLE_ADMIN];
        yield ['/log/excel', self::ROLE_SUPER_ADMIN];
        yield ['/log/refresh', self::ROLE_DISABLED, Response::HTTP_FORBIDDEN];
        yield ['/log/refresh', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/log/refresh', self::ROLE_ADMIN, Response::HTTP_FOUND];
        yield ['/log/refresh', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND];
        yield ['/log/pdf', self::ROLE_DISABLED, Response::HTTP_FORBIDDEN];
        yield ['/log/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/log/pdf', self::ROLE_ADMIN];
        yield ['/log/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/log/show/1', self::ROLE_DISABLED, Response::HTTP_FORBIDDEN];
        yield ['/log/show/1', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/log/show/1', self::ROLE_ADMIN];
        yield ['/log/show/1', self::ROLE_SUPER_ADMIN];
    }
}
