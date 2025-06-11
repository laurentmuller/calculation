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
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTable;
use App\Pdf\PdfStyle;
use App\Service\DatabaseInfoService;

/**
 * Report containing database configuration.
 */
class DatabaseReport extends AbstractReport
{
    private const DISABLED_VALUES = ['off', 'no', 'false', 'disabled'];

    private ?PdfStyle $style = null;

    public function __construct(AbstractController $controller, private readonly DatabaseInfoService $service)
    {
        parent::__construct($controller);
        $this->setTitleTrans('about.database');
    }

    #[\Override]
    public function render(): bool
    {
        $database = $this->service->getDatabase();
        $configuration = $this->service->getConfiguration();
        if ([] === $database && [] === $configuration) {
            return false;
        }

        $this->addPage();
        $table = PdfGroupTable::instance($this)
            ->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                PdfColumn::left('Name', 40),
                PdfColumn::left('Value', 60)
            )->outputHeaders();

        $this->outputArray($table, 'Database', $database);
        $this->outputArray($table, 'Configuration', $configuration);

        return true;
    }

    private function getStyle(string $value): ?PdfStyle
    {
        if (!\in_array(\strtolower($value), self::DISABLED_VALUES, true)) {
            return null;
        }

        return $this->style ??= PdfStyle::getCellStyle()->setTextColor(PdfTextColor::darkGray());
    }

    /**
     * @param array<string, string> $values
     */
    private function outputArray(PdfGroupTable $table, string $title, array $values): void
    {
        if ([] === $values) {
            return;
        }

        $this->addBookmark($title);
        $table->setGroupKey($title);

        foreach ($values as $key => $value) {
            $table->startRow()
                ->add(text: $key)
                ->add(text: $value, style: $this->getStyle($value))
                ->endRow();
        }
    }
}
