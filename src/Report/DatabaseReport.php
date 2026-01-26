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
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Service\DatabaseInfoService;

/**
 * Report containing database configuration.
 */
class DatabaseReport extends AbstractReport
{
    private ?PdfStyle $disableStyle = null;
    private ?PdfStyle $enableStyle = null;

    public function __construct(AbstractController $controller, private readonly DatabaseInfoService $service)
    {
        parent::__construct($controller);
        $this->setTranslatedTitle('about.database.title');
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
        $table = PdfTable::instance($this)
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
        if ($this->service->isEnabledValue($value)) {
            return $this->enableStyle ??= PdfStyle::getCellStyle()->setTextColor(PdfTextColor::darkGreen());
        }
        if ($this->service->isDisabledValue($value)) {
            return $this->disableStyle ??= PdfStyle::getCellStyle()->setTextColor(PdfTextColor::darkGray());
        }

        return null;
    }

    /**
     * @param array<string, string> $values
     */
    private function outputArray(PdfTable $table, string $title, array $values): void
    {
        if ([] === $values) {
            return;
        }

        $table->singleLine($title, PdfStyle::getHeaderStyle());
        foreach ($values as $key => $value) {
            $table->startRow()
                ->add(text: $key)
                ->add(text: $value, style: $this->getStyle($value))
                ->endRow();
        }
    }
}
