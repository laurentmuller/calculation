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
     * Constructor.
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
        $this->createSheetAndTitle($this->controller, 'Bundles');
        $sheet = $this->getActiveSheet();
        $row = $sheet->setHeaders([
            'Name' => HeaderFormat::instance(),
            'Path' => HeaderFormat::instance(),
        ]);
        foreach ($bundles as $bundle) {
            $this->outputRow($row++, $bundle['name'], $bundle['path']);
        }
        $sheet->setAutoSize(1, 2)->finish();
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception if an error occurs
     */
    private function outputGroup(int $row, string $group): self
    {
        $this->setRowValues($row, [$group]);
        $this->mergeCells(1, 2, $row);
        $this->getActiveSheet()->getStyle("A$row")->getFont()->setBold(true);

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
        $this->outputGroup($row++, 'Kernel')
            ->outputRow($row++, 'Environment', $info->getEnvironment())
            ->outputRow($row++, 'Mode', $this->mode)
            ->outputRow($row++, 'Version status', $info->getMaintenanceStatus())
            ->outputRow($row++, 'End of maintenance', $info->getEndOfMaintenance())
            ->outputRow($row++, 'End of product life', $info->getEndOfLife());
        $this->outputGroup($row++, 'Parameters')
            ->outputRow($row++, 'Intl Locale', $this->locale)
            ->outputRow($row++, 'Timezone', $info->getTimeZone())
            ->outputRow($row++, 'Charset', $info->getCharset());
        $this->outputGroup($row++, 'Extensions')
            ->outputRowEnabled($row++, 'Debug', $app->isDebug())
            ->outputRowEnabled($row++, 'OP Cache', $info->isZendCacheLoaded())
            ->outputRowEnabled($row++, 'APCu', $info->isApcuLoaded())
            ->outputRowEnabled($row++, 'Xdebug', $info->isXdebugLoaded());
        $this->outputGroup($row++, 'Directories')
            ->outputRow($row++, 'Project', $info->getProjectDir())
            ->outputRow($row++, 'Logs', $info->getLogInfo())
            ->outputRow($row, 'Cache', $info->getCacheInfo());

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
        $this->createSheetAndTitle($this->controller, $title);
        $row = 1;
        $sheet = $this->getActiveSheet();
        $sheet->setHeaders([
            'Name' => HeaderFormat::instance(),
            'Version' => HeaderFormat::instance(),
            'Description' => HeaderFormat::instance(),
        ], 1, $row++);
        foreach ($packages as $package) {
            $this->outputRow(
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
        $this->createSheetAndTitle($this->controller, $title);
        $sheet = $this->getActiveSheet();
        $row = $sheet->setHeaders([
            'Name' => HeaderFormat::instance(),
            'Path' => HeaderFormat::instance(),
        ]);
        foreach ($routes as $route) {
            $this->outputRow($row++, $route['name'], $route['path']);
        }
        $sheet->setAutoSize(1, 2)
            ->finish();
    }

    private function outputRow(int $row, string ...$values): self
    {
        $this->setRowValues($row, $values);

        return $this;
    }

    private function outputRowEnabled(int $row, string $key, bool $enabled): self
    {
        return $this->outputRow($row, $key, $enabled ? 'Enabled' : 'Disabled');
    }
}
