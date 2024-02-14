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
 * Report for MySql.
 */
class MySqlReport extends AbstractReport
{
    private ?PdfStyle $style = null;

    public function __construct(AbstractController $controller, private readonly DatabaseInfoService $service)
    {
        parent::__construct($controller);
        $this->setTitleTrans('about.mysql_version', ['%version%' => $this->service->getVersion()]);
    }

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
        if (!\in_array(\strtolower($value), ['off', 'no', 'false', 'disabled'], true)) {
            return null;
        }

        if (!$this->style instanceof PdfStyle) {
            $this->style = PdfStyle::getCellStyle()->setTextColor(PdfTextColor::darkGray());
        }

        return $this->style;
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
            $style = $this->getStyle($value);
            $table->startRow()
                ->add($key)
                ->add($value, style: $style)
                ->endRow();
        }
    }
}
