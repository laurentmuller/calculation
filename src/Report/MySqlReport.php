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
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Service\DatabaseInfoService;

/**
 * Report for MySql.
 */
class MySqlReport extends AbstractReport
{
    /**
     * Constructor.
     */
    public function __construct(AbstractController $controller, private readonly DatabaseInfoService $service)
    {
        parent::__construct($controller);
        $this->setTitleTrans('about.mysql_version', ['%version%' => $this->service->getVersion()]);
    }

    /**
     * {@inheritDoc}
     */
    public function render(): bool
    {
        $database = $this->service->getDatabase();
        $configuration = $this->service->getConfiguration();
        if ([] === $database && [] === $configuration) {
            return false;
        }
        $this->AddPage();
        $table = PdfGroupTableBuilder::instance($this)
            ->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                PdfColumn::left('Name', 40),
                PdfColumn::left('Value', 60)
            )->outputHeaders();
        if ([] !== $database) {
            $table->setGroupKey('Database');
            foreach ($database as $key => $value) {
                $table->addRow($key, $value);
            }
        }
        if ([] !== $configuration) {
            $table->setGroupKey('Configuration');
            foreach ($configuration as $key => $value) {
                $table->addRow($key, $value);
            }
        }

        return true;
    }
}
