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
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Twig\Extra\Markdown\MarkdownInterface;

class MarkdownServiceTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testConvertContentRemoveTitle(): void
    {
        $content = '<h1>Hello</h1>';
        $markdown = $this->createMock(MarkdownInterface::class);
        $markdown->method('convert')
            ->willReturnArgument(0);
        $service = new MarkdownService($markdown);
        $actual = $service->convertContent($content, true);
        self::assertSame('', $actual);
    }

    /**
     * @throws Exception
     */
    public function testConvertContentWithTag(): void
    {
        $content = '<h1>Hello</h1>';
        $tags = [
            '<h1>' => '<h5>',
            '</h1>' => '</h5>',
        ];
        $markdown = $this->createMock(MarkdownInterface::class);
        $markdown->method('convert')
            ->willReturnArgument(0);
        $service = new MarkdownService($markdown);
        $actual = $service->convertContent($content, false, $tags);
        self::assertSame('<h5>Hello</h5>', $actual);
    }

    /**
     * @throws Exception
     */
    public function testConvertContentWithTitle(): void
    {
        $content = '<h1>Hello</h1>';
        $markdown = $this->createMock(MarkdownInterface::class);
        $markdown->method('convert')
            ->willReturnArgument(0);
        $service = new MarkdownService($markdown);
        $actual = $service->convertContent($content);
        self::assertSame($content, $actual);
    }

    /**
     * @throws Exception
     */
    public function testConvertFile(): void
    {
        $path = __DIR__ . '/../Data/markdown.md';
        $content = \file_get_contents($path);
        $markdown = $this->createMock(MarkdownInterface::class);
        $markdown->method('convert')
            ->willReturnArgument(0);
        $service = new MarkdownService($markdown);
        $actual = $service->convertFile($path);
        self::assertSame($content, $actual);
    }
}
