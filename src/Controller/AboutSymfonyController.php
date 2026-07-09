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
use App\Traits\RenderPdfDocumentTrait;
use App\Traits\RenderSpreadsheetDocumentTrait;
use App\Utils\FileUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

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
    use RenderPdfDocumentTrait;
    use RenderSpreadsheetDocumentTrait;

    private const array LICENSE_REPLACEMENT = [
        '<h1>' => '<h6>',
        '<h2>' => '<h6>',
        '<h3>' => '<h6>',
        '<h4>' => '<h6>',
        '<h5>' => '<h6>',
        '</h1>' => '</h6>',
        '</h2>' => '</h6>',
        '</h3>' => '</h6>',
        '</h4>' => '</h6>',
        '</h5>' => '</h6>',
    ];

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
    ): JsonResponse {
        if (!$service->hasPackage($name)) {
            return $this->jsonFalse(['message' => $this->trans('about.package.not_found')]);
        }
        $package = $service->getPackage($name);
        if ([] === $package['production'] && [] === $package['development']) {
            return $this->jsonFalse(['message' => $this->trans('about.package.not_found')]);
        }

        $content = $this->renderView('about/symfony_dependency.html.twig', ['package' => $package]);

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
    ): JsonResponse {
        if (!$service->hasPackage($name)) {
            return $this->jsonFalse(['message' => $this->trans('about.licence.not_found')]);
        }
        $package = $service->getPackage($name);
        if (null === $package['licenseFile']) {
            return $this->jsonFalse(['message' => $this->trans('about.licence.not_found')]);
        }
        $license = FileUtils::readFile($package['licenseFile']);
        $content = $this->renderView('about/symfony_license.html.twig', [
            'package' => $package,
            'license' => $license,
            'replacement' => self::LICENSE_REPLACEMENT,
        ]);

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
}
