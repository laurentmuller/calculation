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
    public function testRenderEmpty(): void
    {
        $document = $this->createDocument([]);
        $actual = $document->render();
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
        $document = $this->createDocument($data);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    private function createDocument(array $data): PhpIniDocument
    {
        $helper = self::createStub(DocumentHelperInterface::class);
        $service = self::createMock(PhpInfoService::class);
        $service->method('getVersion')
            ->willReturn(\PHP_VERSION);
        $service->method('asArray')
            ->willReturn($data);
        $service->method('isNoValue')
            ->willReturnCallback(static fn (string $value): bool => 'no value' === $value);
        $service->method('isColor')
            ->willReturnCallback(static fn (string $value): bool => \str_starts_with($value, '#'));

        return new PhpIniDocument($helper, $service);
    }
}
