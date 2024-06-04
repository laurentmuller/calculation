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
use App\Pdf\PdfDocument;
use App\Pdf\PdfFont;
use App\Pdf\PdfFooter;
use App\Pdf\PdfHeader;
use App\Report\AbstractReport;
use App\Tests\TranslatorMockTrait;
use fpdf\PdfDestination;
use fpdf\PdfPageSize;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatableInterface;

#[CoversClass(PdfFooter::class)]
#[CoversClass(PdfHeader::class)]
#[CoversClass(PdfDocument::class)]
class PdfDocumentTest extends TestCase
{
    use TranslatorMockTrait;

    public function testApplyFont(): void
    {
        $doc = new PdfDocument();
        $font = PdfFont::default();
        $oldFont = $doc->applyFont($font);
        self::assertEqualsCanonicalizing($font, $oldFont);
    }

    public function testConstructor(): void
    {
        $doc = new PdfDocument();
        $expected = PdfPageSize::A4->getWidth();
        $actual = $doc->getPageWidth();
        self::assertEqualsWithDelta($expected, $actual, 0.01);
        $expected = PdfPageSize::A4->getHeight();
        $actual = $doc->getPageHeight();
        self::assertEqualsWithDelta($expected, $actual, 0.01);
        $content = $doc->output(PdfDestination::STRING);
        self::assertNotEmpty($content);
    }

    public function testCurrentFont(): void
    {
        $doc = new PdfDocument();
        $font = $doc->getCurrentFont();
        self::assertSame(PdfFont::DEFAULT_SIZE, $font->getSize());
        self::assertSame(PdfFont::DEFAULT_NAME, $font->getName());
        self::assertSame(PdfFont::DEFAULT_STYLE, $font->getStyle());
    }

    public function testFooter(): void
    {
        $doc = new PdfDocument();
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
        $controller = $this->createMock(AbstractController::class);
        $report = new class($controller) extends AbstractReport {
            public function __construct(AbstractController $controller)
            {
                parent::__construct($controller);
            }

            public function render(): bool
            {
                $this->addPage();

                return true;
            }

            public function trans(
                TranslatableInterface|\Stringable|string $id,
                array $parameters = [],
                ?string $domain = null,
                ?string $locale = null
            ): string {
                return 'id';
            }
        };
        $doc = new $report($controller);
        $doc->output(PdfDestination::STRING);
        self::assertSame(1, $doc->getPage());
    }

    public function testHeaderDefault(): void
    {
        $doc = new PdfDocument();
        $header = $doc->getHeader();
        self::assertSame(5.0, $header->getHeight());
    }

    public function testHeaderWithDescription(): void
    {
        $doc = new PdfDocument();
        $doc->applyFont(PdfFont::default());
        $header = $doc->getHeader();
        $header->setDescription('description');
        self::assertSame(9.0, $header->getHeight());
    }

    public function testHeaderWithPrintAddress(): void
    {
        $doc = new PdfDocument();
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
}
