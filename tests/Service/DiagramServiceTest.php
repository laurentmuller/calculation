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
        self::assertCount(2, $this->service);
    }

    public function testGetDiagramFound(): void
    {
        $actual = $this->service->getDiagram('user');
        self::assertSame('User', $actual['title']);
        self::assertSame('user', $actual['name']);
    }

    public function testGetDiagramNotFound(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Unknown diagram name: "fake_name".');
        $this->service->getDiagram('fake_name');
    }

    public function testGetDiagramNoTitle(): void
    {
        $actual = $this->service->getDiagram('no_title');
        self::assertSame('no_title', $actual['name']);
        self::assertSame('No Title', $actual['title']);
    }

    public function testHasDiagram(): void
    {
        self::assertTrue($this->service->hasDiagram('user'));
        self::assertFalse($this->service->hasDiagram('fake_name'));
    }
}
