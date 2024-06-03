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

use App\Pdf\PdfLabel;
use App\Service\PdfLabelService;
use fpdf\PdfException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

#[CoversClass(PdfLabelService::class)]
class PdfLabelServiceTest extends TestCase
{
    public function testAll(): void
    {
        $service = $this->createService();
        $actual = $service->all();
        self::assertNotEmpty($actual);
    }

    public function testAllWithDefaultFile(): void
    {
        $service = $this->createService();
        $actual = $service->all();
        self::assertNotEmpty($actual);
    }

    public function testAllWithEmptyFile(): void
    {
        $file = __DIR__ . '/../Data/empty.txt';
        self::expectException(PdfException::class);
        $service = $this->createService();
        $service->all($file);
    }

    public function testAllWithGivenFile(): void
    {
        $file = __DIR__ . '/../../resources/data/labels.json';
        $service = $this->createService();
        $actual = $service->all($file);
        self::assertNotEmpty($actual);
    }

    public function testAllWithInvalidFile(): void
    {
        self::expectException(PdfException::class);
        $service = $this->createService();
        $service->all(__FILE__);
    }

    public function testAllWithNotExistFile(): void
    {
        self::expectException(PdfException::class);
        $service = $this->createService();
        $service->all('fake');
    }

    public function testGetInvalid(): void
    {
        self::expectException(PdfException::class);
        $service = $this->createService();
        $service->get('fake');
    }

    public function testGetValid(): void
    {
        $service = $this->createService();
        $actual = $service->get('3422');
        self::assertInstanceOf(PdfLabel::class, $actual);
    }

    public function testHas(): void
    {
        $service = $this->createService();
        self::assertTrue($service->has('3422'));
        self::assertFalse($service->has('fake'));
    }

    private function createService(): PdfLabelService
    {
        return new PdfLabelService(new ArrayAdapter());
    }
}
