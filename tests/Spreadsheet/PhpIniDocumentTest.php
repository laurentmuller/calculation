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

namespace App\Tests\Spreadsheet;

use App\Interfaces\DocumentHelperInterface;
use App\Service\PhpInfoService;
use App\Spreadsheet\PhpIniDocument;
use PHPUnit\Framework\TestCase;

final class PhpIniDocumentTest extends TestCase
{
    public function testRender(): void
    {
        $document = $this->createDocument();
        $actual = $document->render();
        self::assertTrue($actual);
    }

    private function createData(): array
    {
        return [
            'version' => \PHP_VERSION,
            'hostname' => null,
            'os' => null,
            'modules' => [
                [
                    'name' => 'Module',
                    'groups' => [
                        [
                            'name' => 'Group',
                            'note' => 'Notes',
                            'headings' => true,
                            'configs' => [
                                [
                                    'name' => 'Config',
                                    'local' => [
                                        'value' => 'locale',
                                        'color' => false,
                                        'no_value' => false,
                                        'redacted' => false,
                                        'enabled' => false,
                                        'disabled' => false,
                                    ],
                                    'master' => [
                                        'value' => 'master',
                                        'color' => false,
                                        'no_value' => false,
                                        'redacted' => false,
                                        'enabled' => false,
                                        'disabled' => false,
                                    ],
                                ],
                                [
                                    'name' => 'Color',
                                    'local' => [
                                        'value' => '#FF8000',
                                        'color' => true,
                                        'no_value' => false,
                                        'redacted' => false,
                                        'enabled' => false,
                                        'disabled' => false,
                                    ],
                                    'master' => null,
                                ],
                                [
                                    'name' => 'No value',
                                    'local' => [
                                        'value' => 'No value',
                                        'color' => false,
                                        'no_value' => true,
                                        'redacted' => false,
                                        'enabled' => false,
                                        'disabled' => false,
                                    ],
                                    'master' => null,
                                ],
                                [
                                    'name' => 'Redacted',
                                    'local' => [
                                        'value' => '********',
                                        'color' => false,
                                        'no_value' => false,
                                        'redacted' => true,
                                        'enabled' => false,
                                        'disabled' => false,
                                    ],
                                    'master' => null,
                                ],
                                [
                                    'name' => 'Enabled',
                                    'local' => [
                                        'value' => 'Enabled',
                                        'color' => false,
                                        'no_value' => false,
                                        'redacted' => false,
                                        'enabled' => true,
                                        'disabled' => false,
                                    ],
                                    'master' => null,
                                ],
                                [
                                    'name' => 'Disabled',
                                    'local' => [
                                        'value' => 'Disabled',
                                        'color' => false,
                                        'no_value' => false,
                                        'redacted' => false,
                                        'enabled' => false,
                                        'disabled' => true,
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

    private function createDocument(): PhpIniDocument
    {
        $phpinfo = $this->createData();
        $helper = self::createStub(DocumentHelperInterface::class);
        $service = self::createStub(PhpInfoService::class);
        $service->method('getPhpInfo')
            ->willReturn($phpinfo);

        return new PhpIniDocument($helper, $service);
    }
}
