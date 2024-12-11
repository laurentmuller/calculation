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
use App\Utils\StringUtils;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Twig\Extra\Markdown\MarkdownInterface;

class MarkdownServiceTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testAddTagClass(): void
    {
        $service = $this->createService();
        $actual = $service->addTagClass('h1', 'my-class', '<h1>Hello</h1>');
        self::assertSame('<h1 class="my-class">Hello</h1>', $actual);
    }

    /**
     * @throws Exception
     */
    public function testAddTagClassNotFound(): void
    {
        $service = $this->createService();
        $actual = $service->addTagClass('fake', 'my-class', '<h1>Hello</h1>');
        self::assertSame('<h1>Hello</h1>', $actual);
    }

    /**
     * @throws Exception
     */
    public function testConvertContentRemoveTitle(): void
    {
        $content = '<h1>Hello</h1>';
        $service = $this->createService();
        $actual = $service->convertContent($content, true);
        self::assertSame('', $actual);
    }

    /**
     * @throws Exception
     */
    public function testConvertContentWithTitle(): void
    {
        $content = '<h1>Hello</h1>';
        $service = $this->createService();
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
        $content = StringUtils::pregReplace('/[^>]$/m', '$0 ', $content);
        $service = $this->createService();
        $actual = $service->convertFile($path);
        self::assertSame($content, $actual);
    }

    /**
     * @throws Exception
     */
    public function testReplaceTag(): void
    {
        $service = $this->createService();
        $actual = $service->replaceTag('h1', 'h4', '<h1>Hello</h1>');
        self::assertSame('<h4>Hello</h4>', $actual);
    }

    /**
     * @throws Exception
     */
    public function testReplaceTagNotFound(): void
    {
        $service = $this->createService();
        $actual = $service->replaceTag('fake', 'h4', '<h1>Hello</h1>');
        self::assertSame('<h1>Hello</h1>', $actual);
    }

    /**
     * @throws Exception
     */
    public function testUpdateTag(): void
    {
        $service = $this->createService();
        $actual = $service->updateTag('h1', 'h4', 'my-class', '<h1>Hello</h1>');
        self::assertSame('<h4 class="my-class">Hello</h4>', $actual);
    }

    /**
     * @throws Exception
     */
    public function testUpdateTagNotFound(): void
    {
        $service = $this->createService();
        $actual = $service->updateTag('fake', 'h4', 'my-class', '<h1>Hello</h1>');
        self::assertSame('<h1>Hello</h1>', $actual);
    }

    /**
     * @throws Exception
     */
    private function createService(): MarkdownService
    {
        $markdown = $this->createMock(MarkdownInterface::class);
        $markdown->method('convert')
            ->willReturnArgument(0);

        return new MarkdownService($markdown);
    }
}
