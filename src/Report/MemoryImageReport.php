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

namespace App\Report;

use App\Controller\AbstractController;
use App\Pdf\Traits\PdfMemoryImageTrait;
use App\Service\ImageService;
use App\Utils\FileUtils;
use fpdf\PdfException;

/**
 * Report to test in memory images.
 */
class MemoryImageReport extends AbstractReport
{
    use PdfMemoryImageTrait;

    public function __construct(
        AbstractController $controller,
        private readonly string $logoFile,
        private readonly ?string $iconFile = null,
        private readonly ?string $screenshotFile = null
    ) {
        parent::__construct($controller);
        $this->setTitle('In memory Images');
    }

    public function render(): bool
    {
        $this->addPage();
        $this->addImageGD();
        $this->addLogoImage();
        $this->addIconImage();
        $this->addScreenshotImage();

        return true;
    }

    private function addIconImage(): void
    {
        if (null === $this->iconFile) {
            return;
        }
        $data = FileUtils::readFile($this->iconFile);
        if ('' === $data) {
            throw PdfException::instance('Unable to get image content.');
        }

        $this->imageMemory($data, 85, 20, 30);
    }

    private function addImageGD(): void
    {
        $service = ImageService::fromTrueColor(200, 150);
        if (!$service instanceof ImageService) {
            throw PdfException::instance('Unable to create image.');
        }

        $service->fill((int) $service->allocateWhite());
        $service->rectangle(0, 0, 199, 149, (int) $service->allocateBlack());
        $service->fillRectangle(30, 100, 30, 48, (int) $service->allocate(255, 0, 0));
        $service->fillRectangle(80, 80, 30, 68, (int) $service->allocate(0, 255, 0));
        $service->fillRectangle(130, 40, 30, 108, (int) $service->allocate(0, 0, 255));

        $this->imageGD($service->getImage(), 160, 20, 40);
    }

    private function addLogoImage(): void
    {
        $data = FileUtils::readFile($this->logoFile);
        if ('' === $data) {
            throw PdfException::instance('Unable to get image content.');
        }

        $this->imageMemory($data, 10, 20, 30);
    }

    private function addScreenshotImage(): void
    {
        if (null === $this->screenshotFile) {
            return;
        }
        $this->image($this->screenshotFile, 10, 70, $this->getPrintableWidth());
    }
}
