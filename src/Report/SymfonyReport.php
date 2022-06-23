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
use App\Util\SymfonyInfo;

/**
 * Report containing Symfony configuration.
 */
class SymfonyReport extends AbstractReport
{
    /**
     * Constructor.
     */
    public function __construct(AbstractController $controller, private readonly SymfonyInfo $info, private readonly string $locale, private readonly string $mode)
    {
        parent::__construct($controller);
        $title = $this->trans('about.symfony');
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
        $info = $this->info;

        $this->AddPage();

        // kernel
        $this->outputInfo($info);

        $bundles = $info->getBundles();
        if (!empty($bundles)) {
            $this->Ln(self::LINE_HEIGHT / 2);
            $this->outputBundles($bundles);
        }

        $packages = $info->getPackages();
        $runtimePackages = $packages[SymfonyInfo::KEY_RUNTIME] ?? [];
        if (!empty($runtimePackages)) {
            $this->Ln(self::LINE_HEIGHT / 2);
            $this->outputPackages('Packages', $runtimePackages);
        }
        $debugPackages = $packages[SymfonyInfo::KEY_DEBUG] ?? [];
        if (!empty($debugPackages)) {
            $this->Ln(self::LINE_HEIGHT / 2);
            $this->outputPackages('Debug Packages', $debugPackages);
        }

        $routes = $info->getRoutes();
        $runtimeRoutes = $routes[SymfonyInfo::KEY_RUNTIME] ?? [];
        if (!empty($runtimeRoutes)) {
            $this->Ln(self::LINE_HEIGHT / 2);
            $this->outputRoutes('Routes', $runtimeRoutes);
        }
        $debugRoutes = $routes[SymfonyInfo::KEY_DEBUG] ?? [];
        if (!empty($debugRoutes)) {
            $this->Ln(self::LINE_HEIGHT / 2);
            $this->outputRoutes('Debug Routes', $debugRoutes);
        }

        return true;
    }

    private function outputBoolRow(PdfGroupTableBuilder $table, string $key, bool $value): self
    {
        return $this->outputRow($table, $key, $value ? 'enabled' : 'disabled');
    }

    /**
     * @param array<array{name: string, path: string}> $bundles
     */
    private function outputBundles(array $bundles): void
    {
        $table = new PdfGroupTableBuilder($this);
        $table->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumn(PdfColumn::left('Name', 30))
            ->addColumn(PdfColumn::left('Path', 70))
            ->setGroupBeforeHeader(true)
            ->setGroupKey('Bundles')
            ->outputHeaders();

        foreach ($bundles as $bundle) {
            $this->outputRow($table, $bundle['name'], $bundle['path']);
        }
    }

    private function outputInfo(SymfonyInfo $info): void
    {
        $app = $this->controller->getApplication();

        $table = new PdfGroupTableBuilder($this);
        $table->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumn(PdfColumn::left('Name', 30))
            ->addColumn(PdfColumn::left('Value', 70))
            ->setGroupKey('Kernel')
            ->outputHeaders();

        $this->outputRow($table, 'Environment', $info->getEnvironment())
            ->outputRow($table, 'Mode', $this->mode)
            ->outputRow($table, 'Intl Locale', $this->locale)
            ->outputRow($table, 'Timezone', $info->getTimeZone())
            ->outputRow($table, 'Charset', $info->getCharset())
            ->outputBoolRow($table, 'Debug', $app->getDebug())
            ->outputBoolRow($table, 'OP Cache', $info->isZendCacheLoaded())
            ->outputBoolRow($table, 'APCu', $info->isApcuLoaded())
            ->outputBoolRow($table, 'Xdebug', $info->isXdebugLoaded())
            ->outputRow($table, 'End of maintenance', $info->getEndOfMaintenanceInfo())
            ->outputRow($table, 'End of product life', $info->getEndOfLifeInfo());

        $table->setGroupKey('Directories');
        $this->outputRow($table, 'Project', $info->getProjectDir())
            ->outputRow($table, 'Logs', $info->getLogInfo())
            ->outputRow($table, 'Cache', $info->getCacheInfo());
    }

    /**
     * @param array<array{name: string, version: string, description: string|null, homepage: string|null}> $packages
     */
    private function outputPackages(string $title, array $packages): void
    {
        $table = new PdfGroupTableBuilder($this);
        $table->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumn(PdfColumn::left('Name', 30))
            ->addColumn(PdfColumn::left('Version', 8))
            ->addColumn(PdfColumn::left('Description', 62))
            ->setGroupBeforeHeader(true)
            ->setGroupKey($title)
            ->outputHeaders();

        foreach ($packages as $package) {
            $cell = new PdfCell(text: $package['name'], link: $package['homepage'] ?? null);
            $table->startRow()
                ->addCell($cell)
                ->add($package['version'])
                ->add($package['description'])
                ->endRow();
        }
    }

    /**
     * @param array<array{name: string, path: string}> $routes
     */
    private function outputRoutes(string $title, array $routes): void
    {
        $table = new PdfGroupTableBuilder($this);
        $table->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumn(PdfColumn::left('Name', 30))
            ->addColumn(PdfColumn::left('Path', 70))
            ->setGroupBeforeHeader(true)
            ->setGroupKey($title)
            ->outputHeaders();

        foreach ($routes as $route) {
            $table->startRow()
                ->add($route['name'])
                ->add($route['path'])
                ->endRow();
        }
    }

    private function outputRow(PdfGroupTableBuilder $table, string $key, string $value): self
    {
        $table->startRow()
            ->add($key)
            ->add($value)
            ->endRow();

        return $this;
    }
}
