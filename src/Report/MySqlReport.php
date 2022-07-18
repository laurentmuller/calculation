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
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function __construct(AbstractController $controller, private readonly DatabaseInfoService $info)
    {
        parent::__construct($controller);
        $title = $this->trans('about.mysql');
        $version = $this->info->getVersion();
        if (!empty($version)) {
            $title .= ' ' . $version;
        }
        $this->SetTitle($title);
    }

    /**
     * {@inheritDoc}
     */
    public function render(): bool
    {
        $database = $this->info->getDatabase();
        $configuration = $this->info->getConfiguration();
        if (empty($database) && empty($configuration)) {
            return false;
        }

        $this->AddPage();
        $table = new PdfGroupTableBuilder($this);
        $table->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                PdfColumn::left('Name', 40),
                PdfColumn::left('Value', 60)
            )->outputHeaders();

        if (!empty($database)) {
            $table->setGroupKey('Database');
            foreach ($database as $key => $value) {
                $table->addRow($key, $value);
            }
        }

        if (!empty($configuration)) {
            $table->setGroupKey('Configuration');
            foreach ($configuration as $key => $value) {
                $table->addRow($key, $value);
            }
        }

        return true;
    }
}
