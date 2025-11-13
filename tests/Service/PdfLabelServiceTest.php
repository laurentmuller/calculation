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

use App\Service\PdfLabelService;
use fpdf\PdfException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class PdfLabelServiceTest extends TestCase
{
    public function testAll(): void
    {
        $service = $this->createLabelService();
        $actual = $service->all();
        self::assertNotEmpty($actual);
    }

    public function testAllWithEmptyFile(): void
    {
        $file = __DIR__ . '/../files/txt/empty.txt';
        self::expectException(PdfException::class);
        $service = $this->createLabelService();
        $service->all($file);
    }

    public function testAllWithGivenFile(): void
    {
        $file = __DIR__ . '/../../resources/data/labels.json';
        $service = $this->createLabelService();
        $actual = $service->all($file);
        self::assertNotEmpty($actual);
    }

    public function testAllWithInvalidFile(): void
    {
        $file = __FILE__;
        self::expectException(PdfException::class);
        $service = $this->createLabelService();
        $service->all($file);
    }

    public function testAllWithNotExistFile(): void
    {
        self::expectException(PdfException::class);
        $service = $this->createLabelService();
        $service->all('fake');
    }

    public function testGetInvalid(): void
    {
        self::expectException(PdfException::class);
        $service = $this->createLabelService();
        $service->get('fake');
    }

    public function testGetValid(): void
    {
        $service = $this->createLabelService();
        $service->get('3422');
        self::expectNotToPerformAssertions();
    }

    public function testHas(): void
    {
        $service = $this->createLabelService();
        self::assertTrue($service->has('3422'));
        self::assertFalse($service->has('fake'));
    }

    private function createLabelService(): PdfLabelService
    {
        return new PdfLabelService(new ArrayAdapter());
    }
}
