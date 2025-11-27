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

namespace App\Controller;

use App\Attribute\ExcelRoute;
use App\Attribute\ForAdmin;
use App\Attribute\GetRoute;
use App\Attribute\PdfRoute;
use App\Enums\Environment;
use App\Report\SymfonyReport;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\BundleInfoService;
use App\Service\KernelInfoService;
use App\Service\PackageInfoService;
use App\Service\RouteInfoService;
use App\Service\SymfonyInfoService;
use App\Spreadsheet\SymfonyDocument;
use App\Traits\ArrayTrait;
use App\Utils\FileUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Extra\Markdown\MarkdownInterface;

/**
 * Controller to output symfony information.
 *
 * @phpstan-import-type PackageType from PackageInfoService
 */
#[ForAdmin]
#[Route(path: '/about/symfony', name: 'about_symfony_')]
class AboutSymfonyController extends AbstractController
{
    use ArrayTrait;

    #[GetRoute(path: '/content', name: 'content')]
    public function content(
        BundleInfoService $bundleService,
        KernelInfoService $kernelService,
        PackageInfoService $packageService,
        RouteInfoService $routeService,
        SymfonyInfoService $symfonyService
    ): JsonResponse {
        $content = $this->renderView('about/symfony_content.html.twig', [
            'kernelService' => $kernelService,
            'bundleService' => $bundleService,
            'routeService' => $routeService,
            'packageService' => $packageService,
            'symfonyService' => $symfonyService,
        ]);

        return $this->jsonTrue(['content' => $content]);
    }

    /**
     * Gets the package dependencies (runtime and development).
     */
    #[GetRoute(path: '/dependency', name: 'dependency')]
    public function dependency(
        #[MapQueryParameter]
        string $name,
        PackageInfoService $service,
        MarkdownInterface $markdown,
    ): JsonResponse {
        $package = $service->getPackage($name);
        if (null === $package || ([] === $package['production'] && [] === $package['development'])) {
            return $this->jsonFalse(['message' => $this->trans('about.package.not_found')]);
        }
        $content = $this->getMarkdownDependency($markdown, $package);

        return $this->jsonTrue(['content' => $content]);
    }

    #[ExcelRoute]
    public function excel(
        BundleInfoService $bundleService,
        KernelInfoService $kernelService,
        PackageInfoService $packageService,
        RouteInfoService $routeService,
        SymfonyInfoService $symfonyService
    ): SpreadsheetResponse {
        $doc = new SymfonyDocument(
            $this,
            $bundleService,
            $kernelService,
            $routeService,
            $packageService,
            $symfonyService
        );

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Gets the package license.
     */
    #[GetRoute(path: '/license', name: 'license')]
    public function license(
        #[MapQueryParameter]
        string $name,
        PackageInfoService $service,
        MarkdownInterface $markdown,
    ): JsonResponse {
        $package = $service->getPackage($name);
        if (null === $package || null === $package['license']) {
            return $this->jsonFalse(['message' => $this->trans('about.licence.not_found')]);
        }
        $content = $this->getMarkdownLicense($markdown, $package);

        return $this->jsonTrue(['content' => $content]);
    }

    #[PdfRoute]
    public function pdf(
        BundleInfoService $bundleService,
        KernelInfoService $kernelService,
        PackageInfoService $packageService,
        RouteInfoService $routeService,
        SymfonyInfoService $symfonyService
    ): PdfResponse {
        $doc = new SymfonyReport(
            $this,
            $bundleService,
            $kernelService,
            $routeService,
            $packageService,
            $symfonyService
        );

        return $this->renderPdfDocument($doc);
    }

    /**
     * @phpstan-param PackageType $package
     */
    private function getMarkdownDependency(MarkdownInterface $markdown, array $package): string
    {
        $content = $this->implodeHeader($package)
            . $this->implodeValues(Environment::PRODUCTION, $package['production'])
            . $this->implodeValues(Environment::DEVELOPMENT, $package['development']);

        return $markdown->convert($content);
    }

    /**
     * @phpstan-param PackageType $package
     */
    private function getMarkdownLicense(MarkdownInterface $markdown, array $package): string
    {
        $content = $this->implodeHeader($package)
            . FileUtils::readFile((string) $package['license']);
        $content = $markdown->convert($content);

        return \strip_tags($content, '<p><h1><h2><h3><h4><h5><h6><em><strong><code><hr>');
    }

    /**
     * @phpstan-param PackageType $package
     */
    private function implodeHeader(array $package): string
    {
        return \sprintf(
            "##### %s\n\nVersion %s - %s\n***\n",
            $package['name'],
            $package['version'],
            $package['time']
        );
    }

    /**
     * @phpstan-param array<string, string> $values
     */
    private function implodeValues(Environment $environment, array $values): string
    {
        if ([] === $values) {
            return '';
        }
        $values = $this->mapKeyAndValue(
            $values,
            static fn (string $key, string $version): string => \sprintf('- %s : `%s`', $key, $version)
        );
        $title = $environment->trans($this->getTranslator());

        return \sprintf("\n\n%s\n\n%s", $title, \implode("\n", $values));
    }
}
