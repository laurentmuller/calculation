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
use App\Pdf\PdfColumn;
use App\Pdf\PdfFontAwesomeCell;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Pdf\Traits\PdfMemoryImageTrait;
use App\Service\FontAwesomeImageService;
use App\Utils\FileUtils;
use fpdf\Enums\PdfMove;
use fpdf\PdfException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FontAwesomeReport extends AbstractReport
{
    use PdfMemoryImageTrait;

    private const int COLUMNS = 3;

    public function __construct(AbstractController $controller, private readonly FontAwesomeImageService $service)
    {
        parent::__construct($controller);
        $this->setTitle('Font Awesome Icons');
    }

    #[\Override]
    public function render(): bool
    {
        $this->addPage();
        $directories = $this->getDirectories();
        foreach ($directories as $directory) {
            $this->renderTitle($directory);
            $this->renderImages($directory);
        }

        return true;
    }

    /**
     * @phpstan-return \Iterator<string, SplFileInfo>
     */
    private function createIterator(string $path): \Iterator
    {
        $pattern = '*' . FontAwesomeImageService::SVG_EXTENSION;
        $finder = Finder::create()
            ->in($path)
            ->name($pattern)
            ->files();

        return new \LimitIterator(iterator: $finder->getIterator(), limit: 45);
    }

    private function createTable(): PdfTable
    {
        $table = PdfTable::instance($this);
        $width = $this->getPrintableWidth() / (float) self::COLUMNS;
        for ($i = 0; $i < self::COLUMNS; ++$i) {
            $table->addColumn(PdfColumn::left(width: $width));
        }

        return $table;
    }

    /**
     * @phpstan-return list<string>
     */
    private function getDirectories(): array
    {
        $pattern = $this->service->getDirectory() . '/*';

        /** @phpstan-var list<string> */
        return \glob($pattern, \GLOB_ONLYDIR);
    }

    private function renderImage(PdfTable $table, int $index, string $directory, string $name): void
    {
        $relativePath = FileUtils::buildPath($directory, $name);
        $image = $this->service->getImage($relativePath);
        if (!$image instanceof FontAwesomeImage) {
            throw PdfException::format('Unable to get image: "%s".', $relativePath);
        }
        if (0 === $index % self::COLUMNS) {
            $table->startRow();
        }
        $table->addCell(new PdfFontAwesomeCell($image, ': ' . $name));
        if (0 === ++$index % self::COLUMNS) {
            $table->endRow();
        }
    }

    private function renderImages(string $path): void
    {
        $index = 0;
        $directory = \basename($path);
        $table = $this->createTable();
        $iterator = $this->createIterator($path);
        foreach ($iterator as $file) {
            $name = $file->getFilenameWithoutExtension();
            $this->renderImage($table, $index, $directory, $name);
            ++$index;
        }
        if ($table->isRowStarted()) {
            $table->completeRow();
        }
        $this->lineBreak(self::LINE_HEIGHT);
    }

    private function renderTitle(string $directory): void
    {
        $text = \ucfirst(\basename($directory));
        $this->useCellMargin(function () use ($text): void {
            PdfStyle::getBoldCellStyle()->apply($this);
            $this->cell(text: $text, move: PdfMove::NEW_LINE);
            $this->resetStyle();
        });
    }
}
