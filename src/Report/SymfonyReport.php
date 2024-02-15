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
        private readonly SymfonyInfoService $service
    ) {
        parent::__construct($controller);
        $this->setTitleTrans('about.symfony_version', ['%version%' => $this->service->getVersion()]);
    }

    public function render(): bool
    {
        $this->addPage();
        $this->outputInfo($this->service);
        $info = $this->service;
        if ([] !== ($bundles = $info->getBundles())) {
            $this->halfLineBreak();
            $this->outputBundles($bundles);
        }
        if ([] !== ($packages = $info->getRuntimePackages())) {
            $this->halfLineBreak();
            $this->outputPackages('Packages', $packages);
        }
        if ([] !== ($packages = $info->getDebugPackages())) {
            $this->halfLineBreak();
            $this->outputPackages('Debug Packages', $packages);
        }
        if ([] !== ($routes = $info->getRuntimeRoutes())) {
            $this->halfLineBreak();
            $this->outputRoutes('Routes', $routes);
        }
        if ([] !== ($routes = $info->getDebugRoutes())) {
            $this->halfLineBreak();
            $this->outputRoutes('Debug Routes', $routes);
        }

        return true;
    }

    private function getStyle(bool $enabled): ?PdfStyle
    {
        if ($enabled) {
            return null;
        }
        if ($this->style instanceof PdfStyle) {
            return $this->style;
        }

        return $this->style = PdfStyle::getCellStyle()->setTextColor(PdfTextColor::darkGray());
    }

    private function halfLineBreak(): void
    {
        $this->lineBreak(self::LINE_HEIGHT / 2.0);
    }

    /**
     * @psalm-param non-empty-array<BundleType> $bundles
     */
    private function outputBundles(array $bundles): void
    {
        $this->addBookmark('Bundles');
        $table = PdfGroupTable::instance($this)
            ->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                PdfColumn::left('Name', 40),
                PdfColumn::left('Path', 70),
                PdfColumn::right('Size', 18, true)
            )
            ->setGroupBeforeHeader(true)
            ->setGroupKey('Bundles')
            ->outputHeaders();
        foreach ($bundles as $bundle) {
            $table->startRow()
                ->addCell(new PdfCell(text: $bundle['name'], link: $bundle['homepage']))
                ->add($bundle['path'])
                ->add($bundle['size'])
                ->endRow();
        }
    }

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
        $this->outputRow($table, 'Environment', $this->trans($info->getEnvironment()))
            ->outputRow($table, 'Running Mode', $this->trans($info->getMode()))
            ->outputRow($table, 'Version status', $info->getMaintenanceStatus())
            ->outputRowEnabled($table, 'Long-Term support', $info->isLongTermSupport())
            ->outputRow($table, 'End of maintenance', $info->getEndOfMaintenance())
            ->outputRow($table, 'End of product life', $info->getEndOfLife());

        $this->addBookmark('Parameters', false, 1);
        $table->setGroupKey('Parameters');
        $this->outputRow($table, 'Intl Locale', $info->getLocaleName())
            ->outputRow($table, 'Timezone', $info->getTimeZone())
            ->outputRow($table, 'Charset', $info->getCharset())
            ->outputRow($table, 'Architecture', $info->getArchitecture());

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
            ->outputRow($table, 'Cache', $info->getCacheInfo())
            ->outputRow($table, 'Build', $info->getBuildInfo());
    }

    /**
     * @psalm-param non-empty-array<string, PackageType> $packages
     */
    private function outputPackages(string $title, array $packages): void
    {
        $this->addBookmark($title);
        $table = PdfGroupTable::instance($this)
            ->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                PdfColumn::left('Name', 40),
                PdfColumn::left('Version', 18, true),
                PdfColumn::left('Description', 70)
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
     */
    private function outputRoutes(string $title, array $routes): void
    {
        $this->addBookmark($title);
        $table = PdfGroupTable::instance($this)
            ->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                PdfColumn::left('Name', 42),
                PdfColumn::left('Path', 69),
                PdfColumn::left('Method', 25, true)
            )
            ->setGroupBeforeHeader(true)
            ->setGroupKey($title)
            ->outputHeaders();
        foreach ($routes as $route) {
            $table->addRow(
                $route['name'],
                $route['path'],
                $route['methods']
            );
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
}
