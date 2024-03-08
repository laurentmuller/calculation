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
        $image = \imagecreate(200, 150);
        if (!$image instanceof \GdImage) {
            return;
        }

        $background = $this->allocateColor($image, 255, 255, 255);
        \imagefilledrectangle($image, 0, 0, 199, 149, $background);

        $border = $this->allocateColor($image, 169, 169, 169);
        \imagerectangle($image, 0, 0, 199, 149, $border);

        $color1 = $this->allocateColor($image, 255, 0, 0);
        \imagefilledrectangle($image, 30, 100, 60, 148, $color1);

        $color2 = $this->allocateColor($image, 0, 255, 0);
        \imagefilledrectangle($image, 80, 80, 110, 148, $color2);

        $color3 = $this->allocateColor($image, 0, 0, 255);
        \imagefilledrectangle($image, 130, 40, 160, 148, $color3);

        $this->imageGD($image, 160, 20, 40);

        // free memory
        \imagecolordeallocate($image, $background);
        \imagecolordeallocate($image, $border);
        \imagecolordeallocate($image, $color1);
        \imagecolordeallocate($image, $color2);
        \imagecolordeallocate($image, $color3);
        \imagedestroy($image);
    }

    private function addImageMemory(): void
    {
        if (!\file_exists($this->image)) {
            return;
        }
        $data = \file_get_contents($this->image);
        if (!\is_string($data)) {
            return;
        }
        $this->imageMemory($data, 10, 20, 30);
    }

    private function allocateColor(\GdImage $image, int $red, int $green, int $blue): int
    {
        $color = \imagecolorallocate($image, $red, $green, $blue);
        if (!\is_int($color)) {
            throw new PdfException('Unable to allocate color.');
        }

        return $color;
    }
}
