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
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class DiagramServiceTest extends TestCase
{
    private DiagramService $service;

    protected function setUp(): void
    {
        $path = __DIR__ . '/../Data/diagrams';
        $cache = new ArrayAdapter();
        $this->service = new DiagramService($path, $cache);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testCount(): void
    {
        $files = $this->service->getFiles();
        self::assertCount(2, $files);
    }

    /**
     * @throws InvalidArgumentException
     */
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

    /**
     * @throws InvalidArgumentException
     */
    public function testGetFileNotFound(): void
    {
        $actual = $this->service->getFile('fake_name');
        self::assertNull($actual);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGetFileNoTitle(): void
    {
        $expected = 'no_title';
        $actual = $this->service->getFile($expected);
        self::assertIsArray($actual);
        self::assertArrayHasKey('name', $actual);
        self::assertArrayHasKey('title', $actual);
        self::assertArrayHasKey('content', $actual);
        self::assertSame($expected, $actual['name']);
    }
}
