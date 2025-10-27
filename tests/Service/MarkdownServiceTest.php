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
use App\Utils\FileUtils;
use App\Utils\StringUtils;
use PHPUnit\Framework\TestCase;
use Twig\Extra\Markdown\MarkdownInterface;

final class MarkdownServiceTest extends TestCase
{
    public function testAddTagClass(): void
    {
        $content = '<h1>Hello</h1>';
        $service = $this->createService();
        $actual = $service->addTagClass('h1', 'my-class', $content);
        self::assertSame('<h1 class="my-class">Hello</h1>', $actual);
    }

    public function testAddTagClassNotFound(): void
    {
        $content = '<h1>Hello</h1>';
        $service = $this->createService();
        $actual = $service->addTagClass('fake', 'my-class', $content);
        self::assertSame($content, $actual);
    }

    public function testConvertContentWithTitle(): void
    {
        $content = '<h1>Hello</h1>';
        $service = $this->createService();
        $actual = $service->convertContent($content);
        self::assertSame($content, $actual);
    }

    public function testConvertFile(): void
    {
        $path = __DIR__ . '/../files/txt/reverse_reader.txt';
        $content = FileUtils::readFile($path);
        $content = StringUtils::pregReplace('/[^>]$/m', '$0 ', $content);
        $service = $this->createService();
        $actual = $service->convertFile($path);
        self::assertSame($content, $actual);
    }

    public function testRemoveTitle(): void
    {
        $content = '<h1>Hello</h1>';
        $service = $this->createService();
        $actual = $service->removeTitle($content);
        self::assertSame('', $actual);
    }

    public function testRemoveTitleEmpty(): void
    {
        $content = '<h4>Hello</h4>';
        $service = $this->createService();
        $actual = $service->removeTitle($content);
        self::assertSame($content, $actual);
    }

    public function testReplaceTag(): void
    {
        $content = '<h1>Hello</h1>';
        $service = $this->createService();
        $actual = $service->replaceTag('h1', 'h4', $content);
        self::assertSame('<h4>Hello</h4>', $actual);
    }

    public function testReplaceTagNotFound(): void
    {
        $content = '<h1>Hello</h1>';
        $service = $this->createService();
        $actual = $service->replaceTag('fake', 'h4', $content);
        self::assertSame($content, $actual);
    }

    public function testUpdateTag(): void
    {
        $content = '<h1>Hello</h1>';
        $service = $this->createService();
        $actual = $service->updateTag('h1', 'h4', 'my-class', $content);
        self::assertSame('<h4 class="my-class">Hello</h4>', $actual);
    }

    public function testUpdateTagNotFound(): void
    {
        $content = '<h1>Hello</h1>';
        $service = $this->createService();
        $actual = $service->updateTag('fake', 'h4', 'my-class', $content);
        self::assertSame($content, $actual);
    }

    public function testUpdateTags(): void
    {
        $content = '<h1>Hello</h1>';
        $tags = [
            ['h1', 'h4', 'my-class'],
        ];
        $service = $this->createService();
        $actual = $service->updateTags($tags, $content);
        self::assertSame('<h4 class="my-class">Hello</h4>', $actual);
    }

    public function testUpdateTagsNoClass(): void
    {
        $content = '<h1>Hello</h1>';
        $tags = [
            ['h1', 'h4'],
        ];
        $service = $this->createService();
        $actual = $service->updateTags($tags, $content);
        self::assertSame('<h4>Hello</h4>', $actual);
    }

    public function testUpdateTagsNotFound(): void
    {
        $content = '<h1>Hello</h1>';
        $tags = [
            ['fake', 'h4', 'my-class'],
        ];
        $service = $this->createService();
        $actual = $service->updateTags($tags, $content);
        self::assertSame($content, $actual);
    }

    private function createService(): MarkdownService
    {
        $markdown = $this->createMock(MarkdownInterface::class);
        $markdown->method('convert')
            ->willReturnArgument(0);

        return new MarkdownService($markdown);
    }
}
