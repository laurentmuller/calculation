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

namespace App\Tests\Report;

use App\Controller\AbstractController;
use App\Report\PhpIniReport;
use App\Service\PhpInfoService;
use PHPUnit\Framework\TestCase;

class PhpIniReportTest extends TestCase
{
    public function testRender(): void
    {
        $data = [
            'bcmath' => [
                'Single' => 'Enabled',
                'Disabled' => 'disabled',
                'Color' => '#FF8000',
                'No Value' => 'no value',
                'Both Values' => [
                    'local' => 0,
                    'master' => 1,
                ],
            ],
            'Empty' => [],
        ];
        $controller = $this->createMock(AbstractController::class);
        $service = $this->createMock(PhpInfoService::class);
        $service->method('asArray')
            ->willReturn($data);

        $report = new PhpIniReport($controller, $service);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderEmpty(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $service = $this->createMock(PhpInfoService::class);
        $service->method('asArray')
            ->willReturn([]);
        $report = new PhpIniReport($controller, $service);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
