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
use App\Word\AbstractHeaderFooter;
use App\Word\AbstractWordDocument;
use App\Word\HtmlDocument;
use App\Word\HtmlWordParser;
use App\Word\WordFooter;
use App\Word\WordHeader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlDocument::class)]
#[CoversClass(AbstractWordDocument::class)]
#[CoversClass(AbstractHeaderFooter::class)]
#[CoversClass(WordHeader::class)]
#[CoversClass(WordFooter::class)]
#[CoversClass(HtmlWordParser::class)]
class HtmlDocumentTest extends TestCase
{
    /**
     * @throws Exception|\PhpOffice\PhpWord\Exception\Exception
     */
    public function testEmptyContent(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $doc = new HtmlDocument($controller, '');
        $actual = $doc->render();
        self::assertFalse($actual);
    }

    /**
     * @throws Exception|\PhpOffice\PhpWord\Exception\Exception
     */
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

    /**
     * @throws Exception|\PhpOffice\PhpWord\Exception\Exception
     */
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

    /**
     * @throws Exception|\PhpOffice\PhpWord\Exception\Exception
     */
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
