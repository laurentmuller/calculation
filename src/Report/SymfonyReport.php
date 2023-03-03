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
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Service\SymfonyInfoService;

/**
 * Report containing Symfony configuration.
 *
 * @psalm-import-type RouteType from SymfonyInfoService
 * @psalm-import-type BundleType from SymfonyInfoService
 * @psalm-import-type PackageType from SymfonyInfoService
 */
class SymfonyReport extends AbstractReport
{
    /**
     * Constructor.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function __construct(AbstractController $controller, private readonly SymfonyInfoService $info, private readonly string $locale, private readonly string $mode)
    {
        parent::__construct($controller);
        $this->setTitleTrans('about.symfony_version', ['%version%' => $this->info->getVersion()]);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function render(): bool
    {
        $info = $this->info;

        $this->AddPage();

        // kernel
        $this->outputInfo($info);

        $bundles = $info->getBundles();
        if ([] !== $bundles) {
            $this->Ln(self::LINE_HEIGHT / 2.0);
            $this->outputBundles($bundles);
        }

        $packages = $info->getPackages();
        $runtimePackages = $packages[SymfonyInfoService::KEY_RUNTIME] ?? [];
        if ([] !== $runtimePackages) {
            $this->Ln(self::LINE_HEIGHT / 2.0);
            $this->outputPackages('Packages', $runtimePackages);
        }
        $debugPackages = $packages[SymfonyInfoService::KEY_DEBUG] ?? [];
        if ([] !== $debugPackages) {
            $this->Ln(self::LINE_HEIGHT / 2.0);
            $this->outputPackages('Debug Packages', $debugPackages);
        }

        $routes = $info->getRoutes();
        $runtimeRoutes = $routes[SymfonyInfoService::KEY_RUNTIME] ?? [];
        if ([] !== $runtimeRoutes) {
            $this->Ln(self::LINE_HEIGHT / 2.0);
            $this->outputRoutes('Routes', $runtimeRoutes);
        }
        $debugRoutes = $routes[SymfonyInfoService::KEY_DEBUG] ?? [];
        if ([] !== $debugRoutes) {
            $this->Ln(self::LINE_HEIGHT / 2.0);
            $this->outputRoutes('Debug Routes', $debugRoutes);
        }

        return true;
    }

    /**
     * @param BundleType[] $bundles
     */
    private function outputBundles(array $bundles): void
    {
        $table = PdfGroupTableBuilder::instance($this)
            ->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                PdfColumn::left('Name', 30),
                PdfColumn::left('Path', 70)
            )
            ->setGroupBeforeHeader(true)
            ->setGroupKey('Bundles')
            ->outputHeaders();

        foreach ($bundles as $bundle) {
            $table->startRow()
                ->addCell(new PdfCell(text: $bundle['name'], link: $bundle['homepage'] ?? ''))
                ->add($bundle['path'])
                ->endRow();
        }
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    private function outputInfo(SymfonyInfoService $info): void
    {
        $app = $this->controller->getApplication();

        $table = PdfGroupTableBuilder::instance($this)
            ->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                PdfColumn::left('Name', 30),
                PdfColumn::left('Value', 70)
            );

        $table->setGroupKey('Kernel')->outputHeaders();
        $this->outputRow($table, 'Environment', $info->getEnvironment())
            ->outputRow($table, 'Mode', $this->mode)
            ->outputRow($table, 'Version status', $info->getMaintenanceStatus())
            ->outputRow($table, 'End of maintenance', $info->getEndOfMaintenance())
            ->outputRow($table, 'End of product life', $info->getEndOfLife());

        $table->setGroupKey('Parameters');
        $this->outputRow($table, 'Intl Locale', $this->locale)
            ->outputRow($table, 'Timezone', $info->getTimeZone())
            ->outputRow($table, 'Charset', $info->getCharset());

        $table->setGroupKey('Extensions');
        $this->outputRowEnabled($table, 'Debug', $app->isDebug())
            ->outputRowEnabled($table, 'OP Cache', $info->isZendCacheLoaded())
            ->outputRowEnabled($table, 'APCu', $info->isApcuLoaded())
            ->outputRowEnabled($table, 'Xdebug', $info->isXdebugLoaded());

        $table->setGroupKey('Directories');
        $this->outputRow($table, 'Project', $info->getProjectDir())
            ->outputRow($table, 'Logs', $info->getLogInfo())
            ->outputRow($table, 'Cache', $info->getCacheInfo());
    }

    /**
     * @param array<string, PackageType> $packages
     */
    private function outputPackages(string $title, array $packages): void
    {
        $table = PdfGroupTableBuilder::instance($this)
            ->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                PdfColumn::left('Name', 30),
                PdfColumn::left('Version', 10),
                PdfColumn::left('Description', 60)
            )
            ->setGroupBeforeHeader(true)
            ->setGroupKey($title)
            ->outputHeaders();

        foreach ($packages as $package) {
            $table->startRow()
                ->addCell(new PdfCell(text: $package['name'], link: $package['homepage']))
                ->add($package['version'])
                ->add($package['description'])
                ->endRow();
        }
    }

    /**
     * @param RouteType[] $routes
     */
    private function outputRoutes(string $title, array $routes): void
    {
        $table = PdfGroupTableBuilder::instance($this)
            ->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                PdfColumn::left('Name', 30),
                PdfColumn::left('Path', 70)
            )
            ->setGroupBeforeHeader(true)
            ->setGroupKey($title)
            ->outputHeaders();

        foreach ($routes as $route) {
            $this->outputRow($table, $route['name'], $route['path']);
        }
    }

    private function outputRow(PdfGroupTableBuilder $table, string $key, string $value): self
    {
        $table->addRow($key, $value);

        return $this;
    }

    private function outputRowEnabled(PdfGroupTableBuilder $table, string $key, bool $enabled): self
    {
        return $this->outputRow($table, $key, $enabled ? 'Enabled' : 'Disabled');
    }
}
