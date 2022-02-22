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

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for {@link App\Controller\LogController} class.
 *
 * @author Laurent Muller
 */
class LogControllerTest extends AbstractControllerTest
{
    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var LoggerInterface $logger */
        $logger = static::getContainer()->get(LoggerInterface::class);
        $logger->info('LogControllerTest: A message for testing purposes.');
    }

    public function getRoutes(): array
    {
        return [
            ['/log', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/log', self::ROLE_ADMIN],
            ['/log', self::ROLE_SUPER_ADMIN],

            ['/log/delete', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/log/delete', self::ROLE_ADMIN],
            ['/log/delete', self::ROLE_SUPER_ADMIN],

            ['/log/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/log/excel', self::ROLE_ADMIN],
            ['/log/excel', self::ROLE_SUPER_ADMIN],

            ['/log/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/log/pdf', self::ROLE_ADMIN],
            ['/log/pdf', self::ROLE_SUPER_ADMIN],

            ['/log/refresh', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/log/refresh', self::ROLE_ADMIN, Response::HTTP_FOUND],
            ['/log/refresh', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND],
        ];
    }
}
