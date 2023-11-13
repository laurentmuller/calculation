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
use App\Service\SymfonyInfoService;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Document containing Symfony configuration.
 *
 * @psalm-import-type RouteType from SymfonyInfoService
 * @psalm-import-type BundleType from SymfonyInfoService
 * @psalm-import-type PackageType from SymfonyInfoService
 */
class SymfonyDocument extends AbstractDocument
{
    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function __construct(AbstractController $controller, private readonly SymfonyInfoService $service, private readonly string $locale, private readonly string $mode)
    {
        parent::__construct($controller);
    }

    public function render(): bool
    {
        $info = $this->service;
        $this->start($this->trans('about.symfony_version', ['%version%' => $info->getVersion()]));
        $this->setActiveTitle('Configuration', $this->controller);
        $this->outputInfo($info);
        $bundles = $info->getBundles();
        if ([] !== $bundles) {
            $this->outputBundles($bundles);
        }
        $packages = $info->getPackages();
        $runtimePackages = $packages[SymfonyInfoService::KEY_RUNTIME] ?? [];
        if ([] !== $runtimePackages) {
            $this->outputPackages('Packages', $runtimePackages);
        }
        $debugPackages = $packages[SymfonyInfoService::KEY_DEBUG] ?? [];
        if ([] !== $debugPackages) {
            $this->outputPackages('Debug Packages', $debugPackages);
        }
        $routes = $info->getRoutes();
        $runtimeRoutes = $routes[SymfonyInfoService::KEY_RUNTIME] ?? [];
        if ([] !== $runtimeRoutes) {
            $this->outputRoutes('Routes', $runtimeRoutes);
        }
        $debugRoutes = $routes[SymfonyInfoService::KEY_DEBUG] ?? [];
        if ([] !== $debugRoutes) {
            $this->outputRoutes('Debug Routes', $debugRoutes);
        }
        $this->setActiveSheetIndex(0);

        return true;
    }

    /**
     * @param BundleType[] $bundles
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function outputBundles(array $bundles): void
    {
        $sheet = $this->createSheetAndTitle($this->controller, 'Bundles');
        $row = $sheet->setHeaders([
            'Name' => HeaderFormat::instance(),
            'Path' => HeaderFormat::instance(),
        ]);
        foreach ($bundles as $bundle) {
            $this->outputRow($sheet, $row++, $bundle['name'], $bundle['path']);
        }
        $sheet->setAutoSize(1, 2)->finish();
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception if an error occurs
     */
    private function outputGroup(WorksheetDocument $sheet, int $row, string $group): self
    {
        $sheet->setRowValues($row, [$group]);
        $sheet->mergeContent(1, 2, $row);
        $sheet->getStyle("A$row")->getFont()->setBold(true);

        return $this;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function outputInfo(SymfonyInfoService $info): void
    {
        $app = $this->controller->getApplication();
        $this->setActiveTitle('Symfony', $this->controller);
        $sheet = $this->getActiveSheet();
        $row = $sheet->setHeaders([
            'Name' => HeaderFormat::instance(),
            'Value' => HeaderFormat::instance(),
        ]);
        $this->outputGroup($sheet, $row++, 'Kernel')
            ->outputRow($sheet, $row++, 'Environment', $info->getEnvironment())
            ->outputRow($sheet, $row++, 'Mode', $this->mode)
            ->outputRow($sheet, $row++, 'Version status', $info->getMaintenanceStatus())
            ->outputRow($sheet, $row++, 'End of maintenance', $info->getEndOfMaintenance())
            ->outputRow($sheet, $row++, 'End of product life', $info->getEndOfLife());

        $this->outputGroup($sheet, $row++, 'Parameters')
            ->outputRow($sheet, $row++, 'Intl Locale', $this->locale)
            ->outputRow($sheet, $row++, 'Timezone', $info->getTimeZone())
            ->outputRow($sheet, $row++, 'Charset', $info->getCharset());

        $this->outputGroup($sheet, $row++, 'Extensions')
            ->outputRowEnabled($sheet, $row++, 'Debug', $app->isDebug())
            ->outputRowEnabled($sheet, $row++, 'OP Cache', $info->isZendCacheLoaded())
            ->outputRowEnabled($sheet, $row++, 'APCu', $info->isApcuLoaded())
            ->outputRowEnabled($sheet, $row++, 'Xdebug', $info->isXdebugLoaded());

        $this->outputGroup($sheet, $row++, 'Directories')
            ->outputRow($sheet, $row++, 'Project', $info->getProjectDir())
            ->outputRow($sheet, $row++, 'Logs', $info->getLogInfo())
            ->outputRow($sheet, $row, 'Cache', $info->getCacheInfo());

        $sheet->setAutoSize(1, 2)
            ->finish();
    }

    /**
     * @param array<string, PackageType> $packages
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function outputPackages(string $title, array $packages): void
    {
        $row = 1;
        $sheet = $this->createSheetAndTitle($this->controller, $title);
        $sheet->setHeaders([
            'Name' => HeaderFormat::instance(),
            'Version' => HeaderFormat::instance(),
            'Description' => HeaderFormat::instance(),
        ], 1, $row++);
        foreach ($packages as $package) {
            $this->outputRow(
                $sheet,
                $row++,
                $package['name'],
                $package['version'],
                $package['description']
            );
        }
        $sheet->getStyle('A:C')
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_TOP);
        $sheet->setAutoSize(1, 2)
            ->setColumnWidth(3, 70, true)
            ->finish();
    }

    /**
     * @param RouteType[] $routes
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function outputRoutes(string $title, array $routes): void
    {
        $sheet = $this->createSheetAndTitle($this->controller, $title);
        $row = $sheet->setHeaders([
            'Name' => HeaderFormat::instance(),
            'Path' => HeaderFormat::instance(),
        ]);
        foreach ($routes as $route) {
            $this->outputRow($sheet, $row++, $route['name'], $route['path']);
        }
        $sheet->setAutoSize(1, 2)
            ->finish();
    }

    private function outputRow(WorksheetDocument $sheet, int $row, string ...$values): self
    {
        $sheet->setRowValues($row, $values);

        return $this;
    }

    private function outputRowEnabled(WorksheetDocument $sheet, int $row, string $key, bool $enabled): self
    {
        return $this->outputRow($sheet, $row, $key, $enabled ? 'Enabled' : 'Disabled');
    }
}
