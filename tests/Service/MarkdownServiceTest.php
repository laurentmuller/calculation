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

use App\Service\MarkdownService;
use PHPUnit\Framework\TestCase;
use Twig\Extra\Markdown\DefaultMarkdown;

final class MarkdownServiceTest extends TestCase
{
    public function testChangeTag(): void
    {
        $path = $this->getFilePath();
        $tags = [
            ['h1', 'p'],
        ];
        $service = $this->createService();
        $actual = $service->processFile($path, $tags, false);
        self::assertSame('<p>Title</p>', $actual);
    }

    public function testEmptyContent(): void
    {
        $path = __DIR__ . '/../files/txt/empty.txt';
        $service = $this->createService();
        $actual = $service->processFile($path);
        self::assertSame('', $actual);
    }

    public function testRemoveTitle(): void
    {
        $path = $this->getFilePath();
        $service = $this->createService();
        $actual = $service->processFile($path);
        self::assertSame('', $actual);
    }

    public function testTagNotFound(): void
    {
        $path = $this->getFilePath();
        $tags = [
            ['p', 'h1'],
        ];
        $service = $this->createService();
        $actual = $service->processFile($path, $tags, false);
        self::assertSame('<h1>Title</h1>', $actual);
    }

    public function testTagWithClass(): void
    {
        $path = $this->getFilePath();
        $tags = [
            ['h1', 'h1', 'my-class'],
        ];
        $service = $this->createService();
        $actual = $service->processFile($path, $tags, false);
        self::assertSame('<h1 class="my-class">Title</h1>', $actual);
    }

    private function createService(): MarkdownService
    {
        return new MarkdownService(new DefaultMarkdown());
    }

    private function getFilePath(): string
    {
        return __DIR__ . '/../files/markdown/title_only.md';
    }
}
