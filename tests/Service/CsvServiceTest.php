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

namespace App\Tests\Service;

use App\Service\CsvService;
use PHPUnit\Framework\TestCase;

final class CsvServiceTest extends TestCase
{
    private const VALUES_SEP = '|';

    public function testInvalidEnclosure(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Field enclosure character must be a single byte character.');
        CsvService::instance(enclosure: 'fake');
    }

    public function testInvalidEscape(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Escape character must be a single byte character or an empty string.');
        CsvService::instance(escape: 'fake');
    }

    public function testInvalidSeparator(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Field separator must be a single byte character.');
        CsvService::instance(separator: 'fake');
    }

    public function testReadResource(): void
    {
        $resource = null;
        $service = $this->getService();

        try {
            $resource = \fopen($this->getFileName(), 'r');
            self::assertIsResource($resource);
            while (!\feof($resource) && null !== $values = $service->parse($resource)) {
                self::assertCount(6, $values);
            }
        } finally {
            if (\is_resource($resource)) {
                \fclose($resource);
            }
        }
    }

    public function testReadString(): void
    {
        $data = '23.02.2023 16:47:53.200|request|INFO|Matched route "homepage".|route_parameters|[]';
        $service = $this->getService();
        $values = $service->parse($data);
        self::assertIsArray($values);
        self::assertCount(6, $values);
    }

    private function getFileName(): string
    {
        return __DIR__ . '/../files/csv/data.csv';
    }

    private function getService(): CsvService
    {
        return CsvService::instance(separator: self::VALUES_SEP);
    }
}
