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
use fpdf\Enums\PdfRectangleStyle;
use fpdf\PdfException;
use fpdf\Traits\PdfEllipseTrait;
use fpdf\Traits\PdfRotationTrait;
use fpdf\Traits\PdfTransparencyTrait;

/**
 * Report to test in memory images.
 */
class MemoryImageReport extends AbstractReport
{
    use PdfEllipseTrait;
    use PdfMemoryImageTrait;
    use PdfRotationTrait;
    use PdfTransparencyTrait;

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
        $this->addTransparencyImage();
        $this->addScreenshotImage();
        $this->renderEllipses();

        $this->addPage();
        $this->renderRotation();

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

        $this->imageMemory($data, 60, 20, 30);
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

    private function addTransparencyImage(): void
    {
        if (null === $this->iconFile) {
            return;
        }
        $data = FileUtils::readFile($this->iconFile);
        if ('' === $data) {
            throw PdfException::instance('Unable to get image content.');
        }

        $this->setAlpha(0.5);
        $this->imageMemory($data, 110, 20, 30);
        $this->resetAlpha();
    }

    private function renderEllipses(): void
    {
        $this->setDrawColor(255, 0, 0);
        $this->ellipse(30, 220, 20, 10);
        $this->circle(65, 220, 10);
        $this->setFillColor(0, 255, 0);
        $this->circle(65, 245, 10, PdfRectangleStyle::BOTH);
        $this->ellipse(30, 245, 20, 10, PdfRectangleStyle::BOTH);
    }

    private function renderRotation(): void
    {
        $this->resetStyle();
        $this->rotateText('My Rotated test', 45, 10, 50);
        $this->rotateRect(50, 30, 20, 10, -45);
        if (null !== $this->iconFile) {
            $this->rotate(45, 60, 40);
            $this->image($this->iconFile);
            $this->endRotate();
        }
    }
}
