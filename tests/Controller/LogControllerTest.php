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
use App\Service\LogService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(LogController::class)]
class LogControllerTest extends ControllerTestCase
{
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

        yield ['/log/show/100000', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND];
    }

    #[Depends('testRoutes')]
    public function testDelete(): void
    {
        $this->checkForm(
            '/log/delete',
            'common.button_delete'
        );
    }

    /**
     * @throws Exception
     */
    public function testDeleteEmpty(): void
    {
        $this->checkEmptyService('/log/delete');
    }

    /**
     * @throws Exception
     */
    public function testDownloadEmpty(): void
    {
        $this->checkEmptyService('/log/download');
    }

    /**
     * @throws Exception
     */
    public function testExcelEmpty(): void
    {
        $this->checkEmptyService('/log/excel');
    }

    /**
     * @throws Exception
     */
    public function testPdfEmpty(): void
    {
        $this->checkEmptyService('/log/pdf');
    }

    /**
     * @throws Exception
     */
    private function checkEmptyService(string $url): void
    {
        $service = $this->createMock(LogService::class);
        $service->method('getLogFile')
            ->willReturn(null);
        self::getContainer()->set(LogService::class, $service);
        $this->checkRoute($url, self::ROLE_ADMIN, Response::HTTP_FOUND);
    }
}
