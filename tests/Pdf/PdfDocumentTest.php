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

namespace App\Tests\Pdf;

use App\Controller\AbstractController;
use App\Model\CustomerInformation;
use App\Pdf\PdfFont;
use App\Tests\Data\TestReport;
use App\Tests\TranslatorMockTrait;
use fpdf\PdfDestination;
use fpdf\PdfPageSize;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class PdfDocumentTest extends TestCase
{
    use TranslatorMockTrait;

    private MockObject&TranslatorInterface $translator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = $this->createMockTranslator();
    }

    /**
     * @throws Exception
     */
    public function testApplyFont(): void
    {
        $doc = $this->createReport();
        $font = PdfFont::default();
        $oldFont = $doc->applyFont($font);
        self::assertEqualsCanonicalizing($font, $oldFont);
    }

    /**
     * @throws Exception
     */
    public function testConstructor(): void
    {
        $doc = $this->createReport();
        $expected = PdfPageSize::A4->getWidth();
        $actual = $doc->getPageWidth();
        self::assertEqualsWithDelta($expected, $actual, 0.01);
        $expected = PdfPageSize::A4->getHeight();
        $actual = $doc->getPageHeight();
        self::assertEqualsWithDelta($expected, $actual, 0.01);
        $content = $doc->output(PdfDestination::STRING);
        self::assertNotEmpty($content);
    }

    /**
     * @throws Exception
     */
    public function testCurrentFont(): void
    {
        $doc = $this->createReport();
        $font = $doc->getCurrentFont();
        self::assertSame(PdfFont::DEFAULT_SIZE, $font->getSize());
        self::assertSame(PdfFont::DEFAULT_NAME, $font->getName());
        self::assertSame(PdfFont::DEFAULT_STYLE, $font->getStyle());
    }

    /**
     * @throws Exception
     */
    public function testFooter(): void
    {
        $doc = $this->createReport();
        $footer = $doc->getFooter();
        $footer->setContent('content', 'https:///www.example.com');
        $doc->output(PdfDestination::STRING);
        self::assertSame(1, $doc->getPage());
    }

    /**
     * @throws Exception
     */
    public function testFooterWithReport(): void
    {
        $doc = $this->createReport();
        $doc->output(PdfDestination::STRING);
        self::assertSame(1, $doc->getPage());
    }

    /**
     * @throws Exception
     */
    public function testHeaderDefault(): void
    {
        $doc = $this->createReport();
        $header = $doc->getHeader();
        self::assertSame(5.0, $header->getHeight());
    }

    /**
     * @throws Exception
     */
    public function testHeaderWithDescription(): void
    {
        $doc = $this->createReport();
        $doc->applyFont(PdfFont::default());
        $header = $doc->getHeader();
        $header->setDescription('description');
        self::assertSame(9.0, $header->getHeight());
    }

    /**
     * @throws Exception
     */
    public function testHeaderWithPrintAddress(): void
    {
        $doc = $this->createReport();
        $doc->applyFont(PdfFont::default());
        $header = $doc->getHeader();
        $info = new CustomerInformation();
        $info->setPrintAddress(true);
        $header->setCustomer($info);
        self::assertSame(12.0, $header->getHeight());
        $header->setDescription('description');
        $doc->output(PdfDestination::STRING);
        self::assertSame(1, $doc->getPage());
    }

    /**
     * @throws Exception
     */
    private function createReport(): TestReport
    {
        $controller = $this->createMock(AbstractController::class);
        $controller->method('getTranslator')
            ->willReturn($this->translator);

        return new TestReport($controller);
    }
}
