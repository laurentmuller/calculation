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
 * @phpstan-import-type RouteType from SymfonyInfoService
 * @phpstan-import-type BundleType from SymfonyInfoService
 * @phpstan-import-type PackageType from SymfonyInfoService
 */
class SymfonyDocument extends AbstractDocument
{
    public function __construct(
        AbstractController $controller,
        private readonly SymfonyInfoService $service
    ) {
        parent::__construct($controller);
    }

    #[\Override]
    public function render(): bool
    {
        $info = $this->service;
        $this->start($this->trans('about.symfony_version', ['%version%' => $info->getVersion()]));
        $this->setActiveTitle('Configuration', $this->controller);
        $this->outputInfo($info);
        $this->outputBundles($info->getBundles());
        $this->outputPackages('Packages', $info->getRuntimePackages());
        $this->outputPackages('Debug Packages', $info->getDebugPackages());
        $this->outputRoutes('Routes', $info->getRuntimeRoutes());
        $this->outputRoutes('Debug Routes', $info->getDebugRoutes());
        $this->setActiveSheetIndex(0);

        return true;
    }

    /**
     * @phpstan-param BundleType[] $bundles
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function outputBundles(array $bundles): void
    {
        if ([] === $bundles) {
            return;
        }
        $sheet = $this->createSheetAndTitle($this->controller, 'Bundles');
        $row = $sheet->setHeaders([
            'Name' => HeaderFormat::instance(),
            'Path' => HeaderFormat::instance(),
            'Size' => HeaderFormat::right(),
        ]);
        foreach ($bundles as $bundle) {
            $sheet->setRowValues($row++, [
                $bundle['name'],
                $bundle['path'],
                $bundle['size'],
            ]);
        }
        $sheet->setAutoSize(1, 2)->finish();
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
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
        $app = $this->controller->getApplicationService();
        $this->setActiveTitle('Symfony', $this->controller);
        $sheet = $this->getActiveSheet();
        $row = $sheet->setHeaders([
            'Name' => HeaderFormat::instance(),
            'Value' => HeaderFormat::instance(),
        ]);
        $this->outputGroup($sheet, $row++, 'Kernel')
            ->outputRow($sheet, $row++, 'Environment', $this->trans($info->getEnvironment()))
            ->outputRow($sheet, $row++, 'Running Mode', $this->trans($info->getMode()))
            ->outputRow($sheet, $row++, 'Version status', $info->getMaintenanceStatus())
            ->outputRowEnabled($sheet, $row++, 'Long-Term support', $info->isLongTermSupport())
            ->outputRow($sheet, $row++, 'End of maintenance', $info->getEndOfMaintenance())
            ->outputRow($sheet, $row++, 'End of product life', $info->getEndOfLife());

        $this->outputGroup($sheet, $row++, 'Parameters')
            ->outputRow($sheet, $row++, 'Intl Locale', $info->getLocaleName())
            ->outputRow($sheet, $row++, 'Timezone', $info->getTimeZone())
            ->outputRow($sheet, $row++, 'Charset', $info->getCharset())
            ->outputRow($sheet, $row++, 'Architecture', $info->getArchitecture());

        $this->outputGroup($sheet, $row++, 'Extensions')
            ->outputRowEnabled($sheet, $row++, 'Debug', $app->isDebug())
            ->outputRowEnabled($sheet, $row++, 'OP Cache', $info->isZendCacheLoaded())
            ->outputRowEnabled($sheet, $row++, 'APCu', $info->isApcuLoaded())
            ->outputRowEnabled($sheet, $row++, 'Xdebug', $info->isXdebugLoaded());

        $this->outputGroup($sheet, $row++, 'Directories')
            ->outputRow($sheet, $row++, 'Project', $info->getProjectDir())
            ->outputRow($sheet, $row++, 'Logs', $info->getLogInfo())
            ->outputRow($sheet, $row, 'Cache', $info->getCacheInfo())
            ->outputRow($sheet, $row, 'Build', $info->getBuildInfo());

        $sheet->setAutoSize(1, 2)
            ->finish();
    }

    private function outputLinkRow(WorksheetDocument $sheet, int $row, string $link, string ...$values): void
    {
        $sheet->setRowValues($row, $values)
            ->setCellLink(1, $row, $link);
    }

    /**
     * @phpstan-param array<string, PackageType> $packages
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function outputPackages(string $title, array $packages): void
    {
        if ([] === $packages) {
            return;
        }
        $row = 1;
        $sheet = $this->createSheetAndTitle($this->controller, $title);
        $sheet->setHeaders([
            'Name' => HeaderFormat::instance(),
            'Version' => HeaderFormat::instance(),
            'Description' => HeaderFormat::instance(),
        ], 1, $row++);
        foreach ($packages as $package) {
            $this->outputLinkRow(
                $sheet,
                $row++,
                $package['homepage'] ?? '',
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
     * @phpstan-param RouteType[] $routes
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function outputRoutes(string $title, array $routes): void
    {
        if ([] === $routes) {
            return;
        }
        $sheet = $this->createSheetAndTitle($this->controller, $title);
        $row = $sheet->setHeaders([
            'Name' => HeaderFormat::instance(),
            'Path' => HeaderFormat::instance(),
            'Method' => HeaderFormat::instance(),
        ]);
        foreach ($routes as $route) {
            $this->outputRow(
                $sheet,
                $row++,
                $route['name'],
                $route['path'],
                $route['methods']
            );
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
