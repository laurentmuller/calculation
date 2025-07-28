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
    public function testRenderEmpty(): void
    {
        $report = $this->createReport([]);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderSuccess(): void
    {
        $data = [
            'First Group' => [
                'single' => 'single',
                'disabled' => 'disabled',
                'no value' => 'no value',
                'entry' => ['local' => 'local', 'master' => 'master'],
                'color' => ['local' => '#FF8000', 'master' => '#0000BB'],
            ],
            'Second Group' => [
                'other' => 'other',
            ],
            'Empty' => [],
        ];
        $report = $this->createReport($data);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    private function createReport(array $data): PhpIniReport
    {
        $controller = $this->createMock(AbstractController::class);
        $service = $this->createMock(PhpInfoService::class);
        $service->method('getVersion')
            ->willReturn(\PHP_VERSION);
        $service->method('asArray')
            ->willReturn($data);
        $service->method('isNoValue')
            ->willReturnCallback(static fn (string $value): bool => 'no value' === $value);
        $service->method('isColor')
            ->willReturnCallback(static fn (string $value): bool => \str_starts_with($value, '#'));

        return new PhpIniReport($controller, $service);
    }
}
