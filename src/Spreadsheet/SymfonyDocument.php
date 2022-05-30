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

namespace App\Spreadsheet;

use App\Controller\AbstractController;
use App\Util\SymfonyInfo;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Document containing Symfony configuration.
 */
class SymfonyDocument extends AbstractDocument
{
    /**
     * Constructor.
     */
    public function __construct(AbstractController $controller, private readonly SymfonyInfo $info, private readonly string $locale, private readonly string $mode)
    {
        parent::__construct($controller);
    }

    /**
     * {@inheritDoc}
     */
    public function render(): bool
    {
        // initialize
        $info = $this->info;
        $version = $info->getVersion();
        $title = $this->trans('about.symfony');
        if (!empty($version)) {
            $title .= ' ' . $version;
        }
        $this->start($title);

        // info
        $this->outputInfo($info);

        // bundles
        $bundles = $info->getBundles();
        if (!empty($bundles)) {
            $this->outputBundles($bundles);
        }

        // packages
        $packages = $info->getPackages();
        $runtimePackages = $packages['runtime'] ?? [];
        if (!empty($runtimePackages)) {
            $this->outputPackages('Packages', $runtimePackages);
        }
        $debugPackages = $packages['debug'] ?? [];
        if (!empty($debugPackages)) {
            $this->outputPackages('Debug Packages', $debugPackages);
        }

        // routes
        $routes = $info->getRoutes();
        $runtimeRoutes = $routes['runtime'] ?? [];
        if (!empty($runtimeRoutes)) {
            $this->outputRoutes('Routes', $runtimeRoutes);
        }
        $debugRoutes = $routes['debug'] ?? [];
        if (!empty($debugRoutes)) {
            $this->outputRoutes('Debug Routes', $debugRoutes);
        }

        $this->setActiveSheetIndex(0);

        return true;
    }

    private function outputBoolRow(int $row, string $key, bool $value): self
    {
        return $this->outputRow($row, $key, $value ? 'enabled' : 'disabled');
    }

    /**
     * @param array<array{name: string, path: string}> $bundles
     */
    private function outputBundles(array $bundles): void
    {
        $this->createSheetAndTitle('Bundles');

        $row = 1;
        $this->setHeaderValues([
            'Name' => Alignment::HORIZONTAL_LEFT,
            'Path' => Alignment::HORIZONTAL_LEFT,
        ], 1, $row++);

        foreach ($bundles as $bundle) {
            $this->outputRow($row++, $bundle['name'], $bundle['path']);
        }

        $this->setAutoSize(1)
            ->setAutoSize(2)
            ->setSelectedCell('A2');
    }

    private function outputGroup(int $row, string $group): self
    {
        $this->setRowValues($row, [$group]);
        $this->mergeCells(1, 2, $row);
        $this->getActiveSheet()->getStyle("A$row")->getFont()->setBold(true);

        return $this;
    }

    private function outputInfo(SymfonyInfo $info): void
    {
        $app = $this->controller->getApplication();
        $this->setActiveTitle('Symfony');

        $row = 1;
        $this->setHeaderValues([
            'Name' => Alignment::HORIZONTAL_LEFT,
            'Value' => Alignment::HORIZONTAL_LEFT,
        ], 1, $row++);

        $this->outputGroup($row++, 'Kernel')
            ->outputRow($row++, 'Environment', $info->getEnvironment())
            ->outputRow($row++, 'Mode', $this->mode)
            ->outputRow($row++, 'Intl Locale', $this->locale)
            ->outputRow($row++, 'Timezone', $info->getTimeZone())
            ->outputRow($row++, 'Charset', $info->getCharset())
            ->outputBoolRow($row++, 'Debug', $app->getDebug())
            ->outputBoolRow($row++, 'OP Cache', $info->isZendCacheLoaded())
            ->outputBoolRow($row++, 'APCu', $info->isApcuLoaded())
            ->outputBoolRow($row++, 'Xdebug', $info->isXdebugLoaded())
            ->outputRow($row++, 'End of maintenance', $info->getEndOfMaintenanceInfo())
            ->outputRow($row++, 'End of product life', $info->getEndOfLifeInfo());

        $this->outputGroup($row++, 'Directories')
            ->outputRow($row++, 'Project', $info->getProjectDir())
            ->outputRow($row++, 'Logs', $info->getLogInfo())
            ->outputRow($row, 'Cache', $info->getCacheInfo());

        $this->setAutoSize(1)
            ->setAutoSize(2)
            ->setSelectedCell('A2');
    }

    /**
     * @param array<array{name: string, version: string, description: string|null, homepage: string|null}> $packages
     */
    private function outputPackages(string $title, array $packages): void
    {
        $this->createSheetAndTitle($title);

        $row = 1;
        $this->setHeaderValues([
            'Name' => Alignment::HORIZONTAL_LEFT,
            'Version' => Alignment::HORIZONTAL_LEFT,
            'Description' => Alignment::HORIZONTAL_LEFT,
        ], 1, $row++);

        foreach ($packages as $package) {
            $this->outputRow($row++, $package['name'], $package['version'], $package['description'] ?? '');
        }

        $this->setAutoSize(1)
            ->setAutoSize(2)
            ->setColumnWidth(3, 70, true)
            ->setSelectedCell('A2');
    }

    /**
     * @param array<array{name: string, path: string}> $routes
     */
    private function outputRoutes(string $title, array $routes): void
    {
        $this->createSheetAndTitle($title);

        $row = 1;
        $this->setHeaderValues([
            'Name' => Alignment::HORIZONTAL_LEFT,
            'Path' => Alignment::HORIZONTAL_LEFT,
        ], 1, $row++);

        foreach ($routes as $route) {
            $this->outputRow($row++, $route['name'], $route['path']);
        }

        $this->setAutoSize(1)
            ->setAutoSize(2)
            ->setSelectedCell('A2');
    }

    private function outputRow(int $row, string ...$values): self
    {
        $this->setRowValues($row, $values);

        return $this;
    }
}