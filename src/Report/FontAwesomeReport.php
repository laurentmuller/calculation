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
use App\Pdf\PdfColumn;
use App\Pdf\PdfFontAwesomeCell;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Pdf\Traits\PdfMemoryImageTrait;
use App\Service\FontAwesomeImageService;
use fpdf\Enums\PdfMove;
use fpdf\PdfException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FontAwesomeReport extends AbstractReport
{
    use PdfMemoryImageTrait;

    private const int COLUMNS = 3;

    public function __construct(DocumentHelperInterface $helper, private readonly FontAwesomeImageService $service)
    {
        parent::__construct($helper);
        $this->properties->setTitle('Font Awesome Icons');
    }

    #[\Override]
    public function render(): bool
    {
        $this->addPage();
        foreach (FontAwesomePath::cases() as $path) {
            $this->renderTitle($path);
            $this->renderImages($path);
        }

        return true;
    }

    /**
     * @return \Iterator<string, SplFileInfo>
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

    private function renderImage(PdfTable $table, int $index, FontAwesomeIcon $icon): void
    {
        $image = $this->service->getImage($icon) ?? throw PdfException::format('Unable to get image: "%s".', $icon->getKey());
        if (0 === $index % self::COLUMNS) {
            $table->startRow();
        }
        $table->addCell(new PdfFontAwesomeCell($image, ': ' . $icon->icon));
        if (0 === ++$index % self::COLUMNS) {
            $table->endRow();
        }
    }

    private function renderImages(FontAwesomePath $path): void
    {
        $index = 0;
        $directory = $path->getPath();
        $table = $this->createTable();
        $iterator = $this->createIterator($directory);
        foreach ($iterator as $file) {
            $name = $file->getFilenameWithoutExtension();
            $icon = new FontAwesomeIcon($path, $name);
            $this->renderImage($table, $index, $icon);
            ++$index;
        }
        if ($table->isRowStarted()) {
            $table->completeRow();
        }
        $this->lineBreak(self::LINE_HEIGHT);
    }

    private function renderTitle(FontAwesomePath $path): void
    {
        $text = \ucfirst($path->value);
        $this->useCellMargin(function () use ($text): void {
            $this->styledCell(
                style: PdfStyle::getBoldCellStyle(),
                text: $text,
                move: PdfMove::NEW_LINE
            );
        });
    }
}
