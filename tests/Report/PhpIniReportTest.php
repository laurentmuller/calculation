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

use App\Interfaces\DocumentHelperInterface;
use App\Report\PhpIniReport;
use App\Service\FontAwesomeCellService;
use App\Service\PhpInfoService;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-import-type InfoType from PhpInfoService
 */
final class PhpIniReportTest extends TestCase
{
    public function testRender(): void
    {
        $report = $this->createReport();
        $actual = $report->render();
        self::assertTrue($actual);
    }

    /**
     * @return InfoType
     */
    private function createData(): array
    {
        return [
            'version' => \PHP_VERSION,
            'hostname' => null,
            'os' => null,
            'modules' => [
                [
                    'name' => 'Module',
                    'size' => 8,
                    'groups' => [
                        [
                            'name' => 'No column',
                            'note' => null,
                            'headers' => null,
                            'configs' => [
                                [
                                    'name' => 'Config',
                                    'local' => [
                                        'value' => 'locale',
                                        'type' => PhpInfoService::TYPE_UNDEFINED,
                                    ],
                                    'master' => [
                                        'value' => 'master',
                                        'type' => PhpInfoService::TYPE_UNDEFINED,
                                    ],
                                ],
                            ],
                        ],
                        [
                            'name' => '2 columns',
                            'note' => 'Note',
                            'headers' => ['Directive', 'Local'],
                            'configs' => [
                                [
                                    'name' => 'Config',
                                    'local' => [
                                        'value' => 'locale',
                                        'type' => PhpInfoService::TYPE_UNDEFINED,
                                    ],
                                    'master' => null,
                                ],
                            ],
                        ],
                        [
                            'name' => '3 columns',
                            'note' => 'Note',
                            'headers' => ['Directive', 'Local', 'Master'],
                            'configs' => [
                                [
                                    'name' => 'Config',
                                    'local' => [
                                        'value' => 'locale',
                                        'type' => PhpInfoService::TYPE_UNDEFINED,
                                    ],
                                    'master' => [
                                        'value' => 'master',
                                        'type' => PhpInfoService::TYPE_UNDEFINED,
                                    ],
                                ],
                                [
                                    'name' => 'Color',
                                    'local' => [
                                        'value' => '#FF8000',
                                        'type' => PhpInfoService::TYPE_COLOR,
                                    ],
                                    'master' => null,
                                ],
                                [
                                    'name' => 'No value',
                                    'local' => [
                                        'value' => 'No value',
                                        'type' => PhpInfoService::TYPE_NO_VALUE,
                                    ],
                                    'master' => null,
                                ],
                                [
                                    'name' => 'None value',
                                    'local' => [
                                        'value' => 'None',
                                        'type' => PhpInfoService::TYPE_NONE_VALUE,
                                    ],
                                    'master' => null,
                                ],
                                [
                                    'name' => 'Redacted',
                                    'local' => [
                                        'value' => '********',
                                        'type' => PhpInfoService::TYPE_REDACTED,
                                    ],
                                    'master' => null,
                                ],
                                [
                                    'name' => 'Enabled',
                                    'local' => [
                                        'value' => 'Enabled',
                                        'type' => PhpInfoService::TYPE_ENABLED,
                                    ],
                                    'master' => null,
                                ],
                                [
                                    'name' => 'Disabled',
                                    'local' => [
                                        'value' => 'Disabled',
                                        'type' => PhpInfoService::TYPE_DISABLED,
                                    ],
                                    'master' => null,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function createReport(): PhpIniReport
    {
        $phpinfo = $this->createData();
        $helper = self::createStub(DocumentHelperInterface::class);
        $cellService = self::createStub(FontAwesomeCellService::class);
        $infoService = self::createStub(PhpInfoService::class);
        $infoService->method('getPhpInfo')
            ->willReturn($phpinfo);

        return new PhpIniReport($helper, $infoService, $cellService);
    }
}
