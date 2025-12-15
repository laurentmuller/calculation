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
        $files = $this->service->getDiagrams();
        self::assertCount(2, $files);
    }

    public function testGetFileFound(): void
    {
        $name = 'user';
        $actual = $this->service->getDiagram($name);
        self::assertArrayHasKey('name', $actual);
        self::assertArrayHasKey('title', $actual);
        self::assertArrayHasKey('content', $actual);
        self::assertSame($name, $actual['name']);
    }

    public function testGetFileNotFound(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Unknown diagram name: "fake_name".');
        $this->service->getDiagram('fake_name');
    }

    public function testGetFileNoTitle(): void
    {
        $name = 'no_title';
        $actual = $this->service->getDiagram($name);
        self::assertArrayHasKey('name', $actual);
        self::assertArrayHasKey('title', $actual);
        self::assertArrayHasKey('content', $actual);
        self::assertSame($name, $actual['name']);
    }

    public function testHasDiagram(): void
    {
        self::assertTrue($this->service->hasDiagram('user'));
        self::assertFalse($this->service->hasDiagram('fake_name'));
    }
}
