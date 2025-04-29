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
use App\Model\FontAwesomeImage;
use App\Model\LogChannel;
use App\Model\LogLevel;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\Html\HtmlBootstrapColor;
use App\Pdf\PdfFontAwesomeCell;
use App\Pdf\PdfStyle;
use App\Pdf\Traits\PdfMemoryImageTrait;
use App\Service\FontAwesomeService;
use App\Service\ImageService;
use App\Utils\FileUtils;
use fpdf\Color\PdfRgbColor;
use fpdf\Enums\PdfMove;
use fpdf\Enums\PdfRectangleStyle;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfBorder;
use fpdf\PdfException;
use fpdf\PdfRectangle;
use fpdf\Traits\PdfEllipseTrait;
use fpdf\Traits\PdfRotationTrait;
use fpdf\Traits\PdfTransparencyTrait;
use Monolog\Level;
use Psr\Log\LogLevel as PsrLevel;

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
        AbstractController $controller,
        private readonly ?string $logoFile = null,
        private readonly ?string $iconFile = null,
        private readonly ?string $screenshotFile = null,
        private readonly ?FontAwesomeService $service = null,
    ) {
        parent::__construct($controller);
        $this->setTitle('In memory Images');
    }

    #[\Override]
    public function render(): bool
    {
        $this->addPage();
        $this->addImageGD();
        $this->addLogoImage();
        $this->addIconImage();
        $this->addTransparencyImage();
        $this->addScreenshotImage();
        $this->renderEllipses();
        $this->renderRotation();
        $this->renderFontAwesome();

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

        $this->imageData($data, 60, 20, 30);
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
        if (null === $this->logoFile) {
            return;
        }
        $data = FileUtils::readFile($this->logoFile);
        if ('' === $data) {
            throw PdfException::instance('Unable to get image content.');
        }

        $this->imageData($data, 10, 20, 30);
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
        $this->imageData($data, 110, 20, 30);
        $this->resetAlpha();
    }

    /**
     * @psalm-param PsrLevel::* $level
     */
    private function getLevelColor(string $level): ?PdfTextColor
    {
        $log = LogLevel::instance($level);
        $color = $log->getLevelColor();

        return HtmlBootstrapColor::parseTextColor($color);
    }

    /**
     * @return array<string, FontAwesomeImage>
     */
    private function getLogChannelImages(): array
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
        $files = [];
        foreach ($channels as $channel) {
            $logChannel = new LogChannel($channel);
            $icon = $this->service?->getPath($logChannel->getChannelIcon());
            if (!\is_string($icon)) {
                continue;
            }
            $image = $this->service?->getImage($icon);
            if ($image instanceof FontAwesomeImage) {
                $files[$channel] = $image;
            }
        }

        return $files;
    }

    /**
     * @return array<string, FontAwesomeImage>
     */
    private function getLogLevelImages(): array
    {
        $files = [];
        $levels = Level::cases();
        foreach ($levels as $level) {
            $logLevel = new LogLevel($level->toPsrLogLevel());
            $color = HtmlBootstrapColor::parseTextColor($logLevel->getLevelColor())?->asHex('#');
            $icon = $this->service?->getPath($logLevel->getLevelIcon());
            if (!\is_string($icon)) {
                continue;
            }
            $image = $this->service?->getImage($icon, $color);
            if (!$image instanceof FontAwesomeImage) {
                continue;
            }
            $files[$logLevel->getLevel()] = $image;
        }

        return $files;
    }

    private function renderCellTitle(string $title): void
    {
        PdfStyle::getBoldCellStyle()->apply($this);
        $this->cell(text: $title, border: PdfBorder::all(), move: PdfMove::NEW_LINE);
        $this->resetStyle();
    }

    private function renderDigits(): void
    {
        $this->renderCellTitle('Digits');

        $color = HtmlBootstrapColor::DANGER->value;
        HtmlBootstrapColor::DANGER->applyTextColor($this);
        foreach (\range(0, 9) as $index) {
            $source = \sprintf('fa-solid fa-%d', $index);
            $icon = $this->service?->getPath($source);
            if (!\is_string($icon)) {
                continue;
            }
            $image = $this->service?->getImage($icon, $color);
            if (!$image instanceof FontAwesomeImage) {
                continue;
            }

            $bounds = new PdfRectangle(
                $this->x,
                $this->y,
                $this->getPrintableWidth(),
                self::LINE_HEIGHT,
            );
            $cell = new PdfFontAwesomeCell($image, $icon);
            $cell->drawImage($this, $bounds, PdfTextAlignment::LEFT, PdfMove::NEW_LINE);
        }
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
        if (!$this->service instanceof FontAwesomeService) {
            return;
        }

        $this->addPage()
            ->resetStyle();

        $this->renderDigits();
        $channelFiles = $this->getLogChannelImages();
        if ([] !== $channelFiles) {
            $this->renderImages('Channels', $channelFiles, false);
        }
        $levelFiles = $this->getLogLevelImages();
        if ([] !== $levelFiles) {
            $this->renderImages('Levels', $levelFiles, true);
        }
    }

    /**
     * @psalm-param array<string, FontAwesomeImage> $files
     */
    private function renderImages(string $title, array $files, bool $color): void
    {
        $this->renderCellTitle($title);
        /** @psalm-var PsrLevel::* $name */
        foreach ($files as $name => $image) {
            if ($color) {
                $this->getLevelColor($name)?->apply($this);
            }
            $bounds = new PdfRectangle(
                $this->getLeftMargin(),
                $this->y,
                $this->getPrintableWidth(),
                self::LINE_HEIGHT,
            );
            $cell = new PdfFontAwesomeCell($image, \ucfirst($name));
            $cell->drawImage($this, $bounds, PdfTextAlignment::LEFT, PdfMove::NEW_LINE);
        }
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
}
