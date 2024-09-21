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
use App\Pdf\PdfGroupTable;
use App\Pdf\PdfStyle;
use App\Pdf\Traits\PdfMemoryImageTrait;
use App\Service\FontAwesomeService;
use fpdf\Enums\PdfTextAlignment;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class FontAwesomeReport extends AbstractReport
{
    use PdfMemoryImageTrait;

    public function __construct(
        AbstractController $controller,
        private readonly FontAwesomeService $service
    ) {
        parent::__construct($controller);
        $this->setTitle('Font Awesome Icons');
    }

    public function render(): bool
    {
        // check
        if ($this->isException()) {
            return true;
        }

        $columns = 3;
        $table = PdfGroupTable::instance($this)
            ->setGroupStyle(PdfStyle::getHeaderStyle());
        $width = $this->getPrintableWidth() / (float) $columns;
        for ($i = 0; $i < $columns; ++$i) {
            $table->addColumn(PdfColumn::left('', $width));
        }
        $total = $this->renderIcons($table, $columns) + $this->renderAliases($table, $columns);
        $this->renderTotal($total);

        return true;
    }

    private function checkGroup(PdfGroupTable $table, SplFileInfo $file, int $total): bool
    {
        $newName = \ucfirst(\dirname($file->getRelativePathname()));
        if ($newName === $table->getGroup()->getKey()) {
            return false;
        }
        if ($table->isRowStarted()) {
            $table->completeRow();
        }
        if (0 !== $total) {
            $this->renderCount($table, $total);
        }
        $this->addPage();
        $this->addBookmark(text: $newName, currentY: false);
        $table->setGroupKey($newName);

        return true;
    }

    /**
     * @psalm-return array<string, array<string, string>>
     */
    private function getGroupedAliases(): array
    {
        $aliases = $this->service->getAliases();
        if ([] === $aliases) {
            return [];
        }

        $results = [];
        foreach ($aliases as $key => $value) {
            $entries = \explode('/', $key);
            $results[$entries[0]][$entries[1]] = $value;
        }
        \ksort($results);

        return $results;
    }

    private function isException(): bool
    {
        // check
        $this->service->getImage('solid/calendar.svg');
        if ($this->service->isSvgSupported() && !$this->service->isImagickException()) {
            return false;
        }

        $this->addPage();
        PdfStyle::getBoldCellStyle()->apply($this);
        $this->cell(text: $this->trans('test.fontawesome_error'), align: PdfTextAlignment::CENTER);

        return true;
    }

    private function renderAliases(PdfGroupTable $table, int $columns): int
    {
        $groups = $this->getGroupedAliases();
        if ([] === $groups) {
            return 0;
        }

        $total = 0;
        $this->addPage();
        $rootName = 'Aliases';
        $this->addBookmark(text: $rootName, currentY: false);
        foreach ($groups as $group => $values) {
            $index = 0;
            $groupName = \ucfirst($group);
            $this->addBookmark(text: $groupName, level: 1);
            $table->setGroupKey(\sprintf('%s - %s', $rootName, $groupName));

            \ksort($values);
            foreach ($values as $key => $value) {
                if ($this->renderImage(
                    $table,
                    $columns,
                    $index,
                    $value,
                    \substr($key, 0, -4)
                )) {
                    ++$index;
                    ++$total;
                }
            }
            if ($table->isRowStarted()) {
                $table->completeRow();
            }
        }
        $this->renderCount($table, $total);

        return $total;
    }

    private function renderIcons(PdfGroupTable $table, int $columns): int
    {
        $total = 0;
        $index = 0;
        $finder = Finder::create()
            ->in($this->service->getSvgDirectory())
            ->name('*' . FontAwesomeService::SVG_EXTENSION)
            ->files();

        foreach ($finder as $file) {
            if ($this->checkGroup($table, $file, $index)) {
                $index = 0;
            }
            if ($this->renderImage(
                $table,
                $columns,
                $index,
                $file->getRelativePathname(),
                $file->getFilenameWithoutExtension()
            )) {
                ++$index;
                ++$total;
            }
        }
        if ($table->isRowStarted()) {
            $table->completeRow();
        }
        $this->renderCount($table, $index);

        return $total;
    }

    private function renderImage(
        PdfGroupTable $table,
        int $columns,
        int $index,
        string $imagePath,
        string $imageText
    ): bool {
        $image = $this->service->getImage($imagePath);
        if (!$image instanceof FontAwesomeImage) {
            return false;
        }
        if (0 === $index % $columns) {
            $table->startRow();
        }
        $cell = new PdfFontAwesomeCell($image, $imageText);
        $table->addCell($cell);
        if (0 === ++$index % $columns) {
            $table->endRow();
        }

        return true;
    }

    private function renderTotal(int $total): void
    {
        $this->lineBreak(1.0);
        PdfStyle::getBoldCellStyle()->apply($this);
        $this->cell(text: $this->translateCount($total), align: PdfTextAlignment::RIGHT);
        $this->resetStyle();
    }
}
