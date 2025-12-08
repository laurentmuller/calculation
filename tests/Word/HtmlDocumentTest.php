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

namespace App\Tests\Word;

use App\Controller\AbstractController;
use App\Word\HtmlDocument;
use PHPUnit\Framework\TestCase;

final class HtmlDocumentTest extends TestCase
{
    public function testEmptyContent(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $doc = new HtmlDocument($controller, '');
        $actual = $doc->render();
        self::assertFalse($actual);
    }

    public function testWithoutTitle(): void
    {
        $content = <<<XML
            <i>Test</i>
            <div>Text</div>
            XML;
        $controller = $this->createMock(AbstractController::class);
        $doc = new HtmlDocument($controller, $content);
        $actual = $doc->render();
        self::assertTrue($actual);
    }

    public function testWithPrintAddress(): void
    {
        $content = <<<XML
            <i>Test</i>
            <div>Text</div>
            XML;
        $controller = $this->createMock(AbstractController::class);
        $doc = new HtmlDocument($controller, $content);
        $doc->setTitle('Title');
        $actual = $doc->render();
        self::assertTrue($actual);
    }

    public function testWithTitle(): void
    {
        $content = <<<XML
            <i>Test</i>
            <div>Text</div>
            XML;
        $controller = $this->createMock(AbstractController::class);
        $doc = new HtmlDocument($controller, $content);
        $doc->setTitle('Title');
        $actual = $doc->render();
        self::assertTrue($actual);
    }
}
