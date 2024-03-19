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
use fpdf\PdfException;

/**
 * Report to test in memory images.
 */
class MemoryImageReport extends AbstractReport
{
    use PdfMemoryImageTrait;

    public function __construct(AbstractController $controller, private readonly string $image)
    {
        parent::__construct($controller);
    }

    public function render(): bool
    {
        $this->setTitle('In memory Images');

        $this->addPage();
        $this->addImageGD();
        $this->addImageMemory();

        return true;
    }

    private function addImageGD(): void
    {
        $service = ImageService::fromTrueColor(200, 150);
        if (!$service instanceof ImageService) {
            throw new PdfException('Unable to create image.');
        }

        $service->fill((int) $service->allocateWhite());
        $service->rectangle(0, 0, 199, 149, (int) $service->allocateBlack());
        $service->fillRectangle(30, 100, 30, 48, (int) $service->allocate(255, 0, 0));
        $service->fillRectangle(80, 80, 30, 68, (int) $service->allocate(0, 255, 0));
        $service->fillRectangle(130, 40, 30, 108, (int) $service->allocate(0, 0, 255));

        $this->imageGD($service->getImage(), 160, 20, 40);
    }

    private function addImageMemory(): void
    {
        if (!\file_exists($this->image)) {
            throw new PdfException('Unable to get image.');
        }

        $data = \file_get_contents($this->image);
        if (!\is_string($data)) {
            throw new PdfException('Unable to get image content.');
        }

        $this->imageMemory($data, 10, 20, 30);
    }
}
