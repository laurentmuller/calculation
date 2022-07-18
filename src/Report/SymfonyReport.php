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
 */
class SymfonyReport extends AbstractReport
{
    /**
     * Constructor.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function __construct(AbstractController $controller, private readonly SymfonyInfoService $info, private readonly string $locale, private readonly string $mode)
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
        if (!empty($bundles)) {
            $this->Ln(self::LINE_HEIGHT / 2);
            $this->outputBundles($bundles);
        }

        $packages = $info->getPackages();
        $runtimePackages = $packages[SymfonyInfoService::KEY_RUNTIME] ?? [];
        if (!empty($runtimePackages)) {
            $this->Ln(self::LINE_HEIGHT / 2);
            $this->outputPackages('Packages', $runtimePackages);
        }
        $debugPackages = $packages[SymfonyInfoService::KEY_DEBUG] ?? [];
        if (!empty($debugPackages)) {
            $this->Ln(self::LINE_HEIGHT / 2);
            $this->outputPackages('Debug Packages', $debugPackages);
        }

        $routes = $info->getRoutes();
        $runtimeRoutes = $routes[SymfonyInfoService::KEY_RUNTIME] ?? [];
        if (!empty($runtimeRoutes)) {
            $this->Ln(self::LINE_HEIGHT / 2);
            $this->outputRoutes('Routes', $runtimeRoutes);
        }
        $debugRoutes = $routes[SymfonyInfoService::KEY_DEBUG] ?? [];
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
            ->addColumns(
                PdfColumn::left('Name', 30),
                PdfColumn::left('Path', 70)
            )->setGroupBeforeHeader(true)
            ->setGroupKey('Bundles')
            ->outputHeaders();

        foreach ($bundles as $bundle) {
            $this->outputRow($table, $bundle['name'], $bundle['path']);
        }
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    private function outputInfo(SymfonyInfoService $info): void
    {
        $app = $this->controller->getApplication();

        $table = new PdfGroupTableBuilder($this);
        $table->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                PdfColumn::left('Name', 30),
                PdfColumn::left('Value', 70)
            )->setGroupKey('Kernel')
            ->outputHeaders();

        $this->outputRow($table, 'Environment', $info->getEnvironment())
            ->outputRow($table, 'Mode', $this->mode)
            ->outputRow($table, 'Intl Locale', $this->locale)
            ->outputRow($table, 'Timezone', $info->getTimeZone())
            ->outputRow($table, 'Charset', $info->getCharset())
            ->outputBoolRow($table, 'Debug', $app->isDebug())
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
     * @param array<string, array{name: string, version: string, description: string, homepage: string}> $packages
     */
    private function outputPackages(string $title, array $packages): void
    {
        $table = new PdfGroupTableBuilder($this);
        $table->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                PdfColumn::left('Name', 30),
                PdfColumn::left('Version', 10),
                PdfColumn::left('Description', 62)
            )->setGroupBeforeHeader(true)
            ->setGroupKey($title)
            ->outputHeaders();

        foreach ($packages as $package) {
            $cell = new PdfCell(text: $package['name'], link: $package['homepage']);
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
            ->addColumns(
                PdfColumn::left('Name', 30),
                PdfColumn::left('Path', 70)
            )->setGroupBeforeHeader(true)
            ->setGroupKey($title)
            ->outputHeaders();

        foreach ($routes as $route) {
            $table->addRow(
                $route['name'],
                $route['path']
            );
        }
    }

    private function outputRow(PdfGroupTableBuilder $table, string $key, string $value): self
    {
        $table->addRow($key, $value);

        return $this;
    }
}
