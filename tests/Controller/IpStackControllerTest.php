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

use App\Service\IpStackService;
use Symfony\Component\HttpFoundation\Response;

class IpStackControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/ipstack', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/ipstack', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
        yield ['/ipstack', self::ROLE_SUPER_ADMIN];
    }

    public function testIpstack(): void
    {
        $data = [
            'ip' => '212.103.73.117',
            'type' => 'ipv4',
        ];
        $service = $this->createMock(IpStackService::class);
        $service->method('getIpInfo')
            ->willReturn($data);
        self::getContainer()->set(IpStackService::class, $service);
        $this->checkRoute('/ipstack', self::ROLE_SUPER_ADMIN);
    }
}
