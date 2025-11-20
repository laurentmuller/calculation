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
use App\Service\BundleInfoService;
use App\Service\KernelInfoService;
use App\Service\PackageInfoService;
use App\Service\RouteInfoService;
use App\Service\SymfonyInfoService;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Document containing Symfony configuration.
 *
 * @phpstan-import-type RouteType from RouteInfoService
 * @phpstan-import-type BundleType from BundleInfoService
 * @phpstan-import-type PackageType from PackageInfoService
 * @phpstan-import-type DirectoryType from KernelInfoService
 */
class SymfonyDocument extends AbstractDocument
{
    public function __construct(
        AbstractController $controller,
        private readonly BundleInfoService $bundleService,
        private readonly KernelInfoService $kernelService,
        private readonly RouteInfoService $routeService,
        private readonly PackageInfoService $packageService,
        private readonly SymfonyInfoService $symfonyService
    ) {
        parent::__construct($controller);
    }

    #[\Override]
    public function render(): bool
    {
        $symfonyService = $this->symfonyService;
        $this->start($this->trans('about.symfony.version', ['%version%' => $symfonyService->getVersion()]));
        $this->setActiveTitle('Configuration', $this->controller);
        $this->outputInfo();
        $this->outputBundles();
        $this->outputPackages('Packages', $this->packageService->getRuntimePackages());
        $this->outputPackages('Debug Packages', $this->packageService->getDebugPackages());
        $this->outputRoutes('Routes', $this->routeService->getRuntimeRoutes());
        $this->outputRoutes('Debug Routes', $this->routeService->getDebugRoutes());
        $this->setActiveSheetIndex(0);

        return true;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function outputBundles(): void
    {
        $bundles = $this->bundleService->getBundles();
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
     * @phpstan-param DirectoryType $info
     */
    private function outputDirectoryRow(WorksheetDocument $sheet, int $row, array $info): self
    {
        return $this->outputRow(
            $sheet,
            $row,
            $info['name'],
            \sprintf('%s (%s)', $info['relative'], $info['size'])
        );
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function outputGroup(WorksheetDocument $sheet, int $row, string $group): self
    {
        $sheet->setRowValues($row, [$group]);
        $sheet->mergeContent(1, 2, $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);

        return $this;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function outputInfo(): void
    {
        $symfonyService = $this->symfonyService;
        $kernelService = $this->kernelService;
        $this->setActiveTitle('Symfony', $this->controller);
        $sheet = $this->getActiveSheet();
        $row = $sheet->setHeaders([
            'Name' => HeaderFormat::instance(),
            'Value' => HeaderFormat::instance(),
        ]);
        $this->outputGroup($sheet, $row++, 'Kernel')
            ->outputRow($sheet, $row++, 'Environment', $this->trans($kernelService->getEnvironment()))
            ->outputRow($sheet, $row++, 'Running Mode', $this->trans($kernelService->getMode()))
            ->outputRow($sheet, $row++, 'Version status', $symfonyService->getMaintenanceStatus())
            ->outputRowEnabled($sheet, $row++, 'Long-Term support', $symfonyService->isLongTermSupport())
            ->outputRow($sheet, $row++, 'End of maintenance', $symfonyService->getEndOfMaintenance())
            ->outputRow($sheet, $row++, 'End of product life', $symfonyService->getEndOfLife());

        $this->outputGroup($sheet, $row++, 'Parameters')
            ->outputRow($sheet, $row++, 'Architecture', $symfonyService->getArchitecture())
            ->outputRow($sheet, $row++, 'Charset', $kernelService->getCharset())
            ->outputRow($sheet, $row++, 'Intl Locale', $symfonyService->getLocaleName())
            ->outputRow($sheet, $row++, 'Timezone', $symfonyService->getTimeZone());

        $this->outputGroup($sheet, $row++, 'Extensions')
            ->outputRowEnabled($sheet, $row++, 'APCu', $symfonyService->isApcuEnabled(), $symfonyService->getApcuStatus())
            ->outputRowEnabled($sheet, $row++, 'Debug', $kernelService->isDebug())
            ->outputRowEnabled($sheet, $row++, 'OPCache', $symfonyService->isOpCacheEnabled(), $symfonyService->getOpCacheStatus())
            ->outputRowEnabled($sheet, $row++, 'Xdebug', $symfonyService->isXdebugEnabled(), $symfonyService->getXdebugStatus());

        $this->outputGroup($sheet, $row++, 'Directories')
            ->outputRow($sheet, $row++, 'Project', $kernelService->getProjectDir())
            ->outputDirectoryRow($sheet, $row++, $kernelService->getCacheInfo())
            ->outputDirectoryRow($sheet, $row++, $kernelService->getBuildInfo())
            ->outputDirectoryRow($sheet, $row, $kernelService->getLogInfo());

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

    private function outputRowEnabled(WorksheetDocument $sheet, int $row, string $key, bool $enabled, ?string $text = null): self
    {
        $text ??= $enabled ? SymfonyInfoService::LABEL_ENABLED : SymfonyInfoService::LABEL_DISABLED;

        return $this->outputRow($sheet, $row, $key, $text);
    }
}
