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
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfException;
use App\Pdf\PdfGroupTable;
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
    private ?PdfStyle $style = null;

    public function __construct(
        AbstractController $controller,
        private readonly SymfonyInfoService $service,
        private readonly string $locale,
        private readonly string $mode
    ) {
        parent::__construct($controller);
        $this->setTitleTrans('about.symfony_version', ['%version%' => $this->service->getVersion()]);
    }

    /**
     * @throws PdfException
     */
    public function render(): bool
    {
        $this->AddPage();
        $this->outputInfo($this->service);

        $bundles = $this->service->getBundles();
        if ([] !== $bundles) {
            $this->Ln(self::LINE_HEIGHT / 2.0);
            $this->outputBundles($bundles);
        }

        $packages = $this->service->getPackages();
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

        $routes = $this->service->getRoutes();
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

    private function getStyle(bool $enabled): ?PdfStyle
    {
        if ($enabled) {
            return null;
        }

        if (!$this->style instanceof PdfStyle) {
            $this->style = PdfStyle::getCellStyle()->setTextColor(PdfTextColor::darkGray());
        }

        return $this->style;
    }

    /**
     * @psalm-param non-empty-array<BundleType> $bundles
     *
     * @throws PdfException
     */
    private function outputBundles(array $bundles): void
    {
        $this->addBookmark('Bundles');
        $table = PdfGroupTable::instance($this)
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
     * @throws PdfException
     */
    private function outputInfo(SymfonyInfoService $info): void
    {
        $this->addBookmark('Kernel');
        $app = $this->controller->getApplication();
        $table = PdfGroupTable::instance($this)
            ->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                PdfColumn::left('Name', 30),
                PdfColumn::left('Value', 70)
            );
        $table->setGroupKey('Kernel')->outputHeaders();
        $this->outputRow($table, 'Environment', $this->transEnvironment($info->getEnvironment()))
            ->outputRow($table, 'Running Mode', $this->transEnvironment($this->mode))
            ->outputRow($table, 'Version status', $info->getMaintenanceStatus())
            ->outputRow($table, 'End of maintenance', $info->getEndOfMaintenance())
            ->outputRow($table, 'End of product life', $info->getEndOfLife());

        $this->addBookmark('Parameters', false, 1);
        $table->setGroupKey('Parameters');
        $this->outputRow($table, 'Intl Locale', $this->locale)
            ->outputRow($table, 'Timezone', $info->getTimeZone())
            ->outputRow($table, 'Charset', $info->getCharset());

        $this->addBookmark('Extensions', false, 1);
        $table->setGroupKey('Extensions');
        $this->outputRowEnabled($table, 'Debug', $app->isDebug())
            ->outputRowEnabled($table, 'OP Cache', $info->isZendCacheLoaded())
            ->outputRowEnabled($table, 'APCu', $info->isApcuLoaded())
            ->outputRowEnabled($table, 'Xdebug', $info->isXdebugLoaded());

        $this->addBookmark('Directories', false, 1);
        $table->setGroupKey('Directories');
        $this->outputRow($table, 'Project', $info->getProjectDir())
            ->outputRow($table, 'Logs', $info->getLogInfo())
            ->outputRow($table, 'Cache', $info->getCacheInfo());
    }

    /**
     * @psalm-param non-empty-array<string, PackageType> $packages
     *
     * @throws PdfException
     */
    private function outputPackages(string $title, array $packages): void
    {
        $this->addBookmark($title);
        $table = PdfGroupTable::instance($this)
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
     * @psalm-param non-empty-array<RouteType> $routes
     *
     * @throws PdfException
     */
    private function outputRoutes(string $title, array $routes): void
    {
        $this->addBookmark($title);
        $table = PdfGroupTable::instance($this)
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

    private function outputRow(PdfGroupTable $table, string $key, string $value): self
    {
        $table->addRow($key, $value);

        return $this;
    }

    private function outputRowEnabled(PdfGroupTable $table, string $key, bool $enabled): self
    {
        $style = $this->getStyle($enabled);
        $text = $enabled ? 'Enabled' : 'Disabled';
        $table->startRow()
            ->add($key)
            ->add($text, style: $style)
            ->endRow();

        return $this;
    }

    private function transEnvironment(string $environment): string
    {
        return $this->trans('environment.' . $environment);
    }
}
