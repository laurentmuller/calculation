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

use App\Service\DiagramService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class DiagramServiceTest extends TestCase
{
    private DiagramService $service;

    #[\Override]
    protected function setUp(): void
    {
        $path = __DIR__ . '/../files/diagrams';
        $cache = new ArrayAdapter();
        $this->service = new DiagramService($path, $cache);
    }

    public function testCount(): void
    {
        $files = $this->service->getFiles();
        self::assertCount(2, $files);
    }

    public function testGetFileFound(): void
    {
        $expected = 'user';
        $actual = $this->service->getFile($expected);
        self::assertIsArray($actual);
        self::assertArrayHasKey('name', $actual);
        self::assertArrayHasKey('title', $actual);
        self::assertArrayHasKey('content', $actual);
        self::assertSame($expected, $actual['name']);
    }

    public function testGetFileNotFound(): void
    {
        $actual = $this->service->getFile('fake_name');
        self::assertNull($actual);
    }

    public function testGetFileNoTitle(): void
    {
        $file = 'no_title';
        $actual = $this->service->getFile($file);
        self::assertIsArray($actual);
        self::assertSame($file, $actual['name']);
        self::assertSame('No Title', $actual['title']);
    }
}
