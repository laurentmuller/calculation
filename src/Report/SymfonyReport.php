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
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTable;
use App\Pdf\PdfStyle;
use App\Service\BundleInfoService;
use App\Service\KernelInfoService;
use App\Service\PackageInfoService;
use App\Service\RouteInfoService;
use App\Service\SymfonyInfoService;

/**
 * Report containing Symfony configuration.
 *
 * @phpstan-import-type RouteType from RouteInfoService
 * @phpstan-import-type BundleType from BundleInfoService
 * @phpstan-import-type PackageType from PackageInfoService
 * @phpstan-import-type DirectoryType from KernelInfoService
 */
class SymfonyReport extends AbstractReport
{
    private ?PdfStyle $style = null;

    public function __construct(
        AbstractController $controller,
        private readonly BundleInfoService $bundleService,
        private readonly KernelInfoService $kernelService,
        private readonly RouteInfoService $routeService,
        private readonly PackageInfoService $packageService,
        private readonly SymfonyInfoService $symfonyService
    ) {
        parent::__construct($controller);
        $this->setTranslatedTitle('about.symfony.version', ['%version%' => $symfonyService->getVersion()]);
    }

    #[\Override]
    public function render(): bool
    {
        $this->addPage();
        $this->outputInfo();
        $this->outputBundles();
        $this->outputPackages('Packages', $this->packageService->getRuntimePackages());
        $this->outputPackages('Debug Packages', $this->packageService->getDebugPackages());
        $this->outputRoutes('Routes', $this->routeService->getRuntimeRoutes());
        $this->outputRoutes('Debug Routes', $this->routeService->getDebugRoutes());

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

    private function outputBundles(): void
    {
        $bundles = $this->bundleService->getBundles();
        if ([] === $bundles) {
            return;
        }
        $this->halfLineBreak();
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
            $table->addRow($bundle['name'], $bundle['path'], $bundle['size']);
        }
    }

    /**
     * @phpstan-param DirectoryType $info
     */
    private function outputDirectoryRow(PdfGroupTable $table, array $info): self
    {
        return $this->outputRow(
            $table,
            $info['name'],
            \sprintf('%s (%s)', $info['relative'], $info['size'])
        );
    }

    private function outputInfo(): void
    {
        $symfonyService = $this->symfonyService;
        $kernelService = $this->kernelService;

        $this->addBookmark('Kernel');
        $table = PdfGroupTable::instance($this)
            ->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                PdfColumn::left('Name', 42),
                PdfColumn::left('Value', 85)
            );
        $table->setGroupKey('Kernel')->outputHeaders();
        $this->outputRow($table, 'Environment', $this->trans($kernelService->getEnvironment()))
            ->outputRow($table, 'Running Mode', $this->trans($kernelService->getMode()))
            ->outputRow($table, 'Version status', $symfonyService->getMaintenanceStatus())
            ->outputRowEnabled($table, 'Long-Term support', $symfonyService->isLongTermSupport())
            ->outputRow($table, 'End of maintenance', $symfonyService->getEndOfMaintenance())
            ->outputRow($table, 'End of product life', $symfonyService->getEndOfLife());

        $this->addBookmark('Parameters', false, 1);
        $table->setGroupKey('Parameters');
        $this->outputRow($table, 'Architecture', $symfonyService->getArchitecture())
            ->outputRow($table, 'Charset', $kernelService->getCharset())
            ->outputRow($table, 'Intl Locale', $symfonyService->getLocaleName())
            ->outputRow($table, 'Timezone', $symfonyService->getTimeZone());

        $this->addBookmark('Extensions', false, 1);
        $table->setGroupKey('Extensions');
        $this->outputRowEnabled($table, 'APCu', $symfonyService->isApcuEnabled(), $symfonyService->getApcuStatus())
            ->outputRowEnabled($table, 'Debug', $kernelService->isDebug())
            ->outputRowEnabled($table, 'OPCache', $symfonyService->isOpCacheEnabled(), $symfonyService->getOpCacheStatus())
            ->outputRowEnabled($table, 'Xdebug', $symfonyService->isXdebugEnabled(), $symfonyService->getXdebugStatus());

        $this->addBookmark('Directories', false, 1);
        $table->setGroupKey('Directories');
        $this->outputRow($table, 'Project', $kernelService->getProjectDir())
            ->outputDirectoryRow($table, $kernelService->getCacheInfo())
            ->outputDirectoryRow($table, $kernelService->getBuildInfo())
            ->outputDirectoryRow($table, $kernelService->getLogInfo());
    }

    /**
     * @phpstan-param array<string, PackageType> $packages
     */
    private function outputPackages(string $title, array $packages): void
    {
        if ([] === $packages) {
            return;
        }
        $this->halfLineBreak();
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
                ->add($package['name'], link: $package['homepage'])
                ->add($package['version'])
                ->add($package['description'])
                ->endRow();
        }
    }

    /**
     * @phpstan-param RouteType[] $routes
     */
    private function outputRoutes(string $title, array $routes): void
    {
        if ([] === $routes) {
            return;
        }
        $this->halfLineBreak();
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

    private function outputRowEnabled(PdfGroupTable $table, string $key, bool $enabled, ?string $text = null): self
    {
        $style = $this->getStyle($enabled);
        $text ??= $enabled ? SymfonyInfoService::LABEL_ENABLED : SymfonyInfoService::LABEL_DISABLED;
        $table->startRow()
            ->add($key)
            ->add($text, style: $style)
            ->endRow();

        return $this;
    }
}
