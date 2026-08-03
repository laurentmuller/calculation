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

use App\Enums\FontAwesomePath;
use App\Interfaces\DocumentHelperInterface;
use App\Model\FontAwesomeIcon;
use App\Model\LogChannel;
use App\Model\LogLevel;
use App\Pdf\PdfColumn;
use App\Pdf\PdfFontAwesomeCell;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Pdf\Traits\PdfMemoryImageTrait;
use App\Service\FontAwesomeCellService;
use App\Service\ImageService;
use App\Utils\FileUtils;
use fpdf\Color\PdfRgbColor;
use fpdf\Enums\PdfRectangleStyle;
use fpdf\PdfBorder;
use fpdf\PdfException;
use fpdf\Traits\PdfEllipseTrait;
use fpdf\Traits\PdfRotationTrait;
use fpdf\Traits\PdfTransparencyTrait;
use Monolog\Level;

/**
 * Report testing in memory images.
 */
class MemoryImageReport extends AbstractReport
{
    use PdfEllipseTrait;
    use PdfMemoryImageTrait;
    use PdfRotationTrait;
    use PdfTransparencyTrait;

    public function __construct(
        DocumentHelperInterface $helper,
        private readonly ?string $logoFile = null,
        private readonly ?string $iconFile = null,
        private readonly ?string $transparencyFile = null,
        private readonly ?string $screenshotFile = null,
        private readonly ?FontAwesomeCellService $service = null,
    ) {
        parent::__construct($helper);
        $this->properties->setTitle('In memory Images');
    }

    #[\Override]
    public function render(): bool
    {
        $this->addPage();
        $this->renderImageGD();
        $this->renderLogoImage();
        $this->renderIconImage();
        $this->renderTransparencyImage();
        $this->renderScreenshotImage();
        $this->renderEllipses();
        $this->renderRotation();
        $this->renderFontAwesome();

        return true;
    }

    /**
     * @param non-empty-array<PdfFontAwesomeCell> $cells
     */
    private function renderCells(string $title, array $cells): void
    {
        $table = new PdfTable($this);
        $table->setCellStyle(PdfStyle::default()->setBorder(PdfBorder::none()))
            ->addColumn(new PdfColumn(\ucfirst($title)))
            ->outputHeaders();
        foreach ($cells as $cell) {
            $table->addRow($cell);
        }
    }

    private function renderDigits(): self
    {
        $cells = [];
        foreach (\range('0', '9') as $index) {
            $icon = new FontAwesomeIcon(FontAwesomePath::SOLID, $index);
            $cell = $this->service?->getCell(
                icon: $icon,
                text: $index
            );
            if ($cell instanceof PdfFontAwesomeCell) {
                $cells[] = $cell;
            }
        }
        if ([] !== $cells) {
            $this->renderCells('Digits', $cells);
        }

        return $this;
    }

    private function renderEllipses(): void
    {
        $this->setDrawColor(PdfRgbColor::red());
        $this->ellipse(30, 220, 20, 10);
        $this->circle(65, 220, 10);
        $this->setFillColor(PdfRgbColor::green());
        $this->circle(65, 245, 10, PdfRectangleStyle::BOTH);
        $this->ellipse(30, 245, 20, 10, PdfRectangleStyle::BOTH);
    }

    private function renderFontAwesome(): void
    {
        if ($this->service instanceof FontAwesomeCellService) {
            $this->addPage()
                ->resetStyle()
                ->renderDigits()
                ->renderLogChannels()
                ->renderLogLevels();
        }
    }

    private function renderIconImage(): void
    {
        if (null === $this->iconFile) {
            return;
        }
        $data = FileUtils::readFile($this->iconFile);
        if (null === $data) {
            throw PdfException::instance('Unable to get image content.');
        }

        $this->imageData($data, 60, 20, 30);
    }

    private function renderImageGD(): void
    {
        $service = ImageService::fromTrueColor(200, 150);
        $service->fill($service->allocateWhite());
        $service->rectangle(0, 0, 199, 149, $service->allocateBlack());
        $service->fillRectangle(30, 100, 30, 48, $service->allocate(255, 0, 0));
        $service->fillRectangle(80, 80, 30, 68, $service->allocate(0, 255, 0));
        $service->fillRectangle(130, 40, 30, 108, $service->allocate(0, 0, 255));
        $this->imageGD($service->getImage(), 160, 20, 40);
    }

    private function renderLogChannels(): self
    {
        $channels = [
            'application',
            'cache',
            'console',
            'doctrine',
            'mailer',
            'php',
            'request',
            'security',
            'deprecation',
            'file',
        ];
        $cells = [];
        foreach ($channels as $channel) {
            $logChannel = new LogChannel($channel);
            $logIcon = $logChannel->getChannelFontAwesomeIcon();
            $logCell = $this->service?->getCell(
                icon: $logIcon,
                text: \ucfirst($channel)
            );
            if ($logCell instanceof PdfFontAwesomeCell) {
                $cells[] = $logCell;
            }
        }
        if ([] !== $cells) {
            $this->renderCells('Channels', $cells);
        }

        return $this;
    }

    private function renderLogLevels(): self
    {
        $cells = [];
        $levels = Level::cases();
        foreach ($levels as $level) {
            $logLevel = new LogLevel($level->toPsrLogLevel());
            $logIcon = $logLevel->getLevelFontAwesomeIcon();
            $logColor = $logLevel->getLevelBootstrapColor()->asHex('#');
            $logCell = $this->service?->getCell(
                icon: $logIcon,
                color: $logColor,
                text: \ucfirst($logLevel->getLevel())
            );
            if ($logCell instanceof PdfFontAwesomeCell) {
                $cells[] = $logCell;
            }
        }
        if ([] !== $cells) {
            $this->renderCells('Levels', $cells);
        }

        return $this;
    }

    private function renderLogoImage(): void
    {
        if (null === $this->logoFile) {
            return;
        }
        $data = FileUtils::readFile($this->logoFile);
        if (null === $data) {
            throw PdfException::instance('Unable to get image content.');
        }

        $this->imageData($data, 10, 20, 30);
    }

    private function renderRotation(): void
    {
        if (null === $this->iconFile) {
            return;
        }
        $this->addPage();
        $this->resetStyle();
        $this->rotateText('My Rotated test', 45, 10, 50);
        $this->rotateRect(50, 30, 20, 10, -45);
        $this->rotate(45, 60, 40);
        $this->image($this->iconFile);
        $this->endRotate();
    }

    private function renderScreenshotImage(): void
    {
        if (null === $this->screenshotFile) {
            return;
        }
        $this->image($this->screenshotFile, 10, 70, $this->getPrintableWidth());
    }

    private function renderTransparencyImage(): void
    {
        if (null === $this->transparencyFile) {
            return;
        }
        $data = FileUtils::readFile($this->transparencyFile);
        if (null === $data) {
            throw PdfException::instance('Unable to get image content.');
        }
        $this->setAlpha(0.5);
        $this->imageData($data, 110, 20, 30);
        $this->resetAlpha();
    }
}
