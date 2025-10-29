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
use App\Attribute\GetRoute;
use App\Attribute\PdfRoute;
use App\Enums\Environment;
use App\Interfaces\RoleInterface;
use App\Report\SymfonyReport;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\SymfonyInfoService;
use App\Spreadsheet\SymfonyDocument;
use App\Utils\FileUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Extra\Markdown\MarkdownInterface;

/**
 * Controller to output symfony information.
 *
 * @phpstan-import-type PackageType from SymfonyInfoService
 */
#[IsGranted(RoleInterface::ROLE_ADMIN)]
#[Route(path: '/about/symfony', name: 'about_symfony_')]
class AboutSymfonyController extends AbstractController
{
    #[IsGranted(RoleInterface::ROLE_ADMIN)]
    #[GetRoute(path: '/content', name: 'content')]
    public function content(SymfonyInfoService $service): JsonResponse
    {
        $content = $this->renderView('about/symfony_content.html.twig', ['service' => $service]);

        return $this->jsonTrue(['content' => $content]);
    }

    #[ExcelRoute]
    public function excel(SymfonyInfoService $service): SpreadsheetResponse
    {
        return $this->renderSpreadsheetDocument(new SymfonyDocument($this, $service));
    }

    /**
     * Gets the package license.
     */
    #[GetRoute(path: '/license', name: 'license')]
    public function license(
        #[MapQueryParameter]
        string $name,
        SymfonyInfoService $service,
        MarkdownInterface $markdown,
    ): JsonResponse {
        $package = $service->getPackage($name);
        if (null === $package || null === $package['license']) {
            return $this->jsonFalse(['message' => $this->trans('about.licence.not_found')]);
        }
        $content = $this->getMarkdownLicense($package);

        return $this->jsonTrue(['content' => $markdown->convert($content)]);
    }

    /**
     * Gets the package definition.
     */
    #[GetRoute(path: '/package', name: 'package')]
    public function package(
        #[MapQueryParameter]
        string $name,
        SymfonyInfoService $service,
        MarkdownInterface $markdown,
    ): JsonResponse {
        $package = $service->getPackage($name);
        if (null === $package) {
            return $this->jsonFalse(['message' => $this->trans('about.package.not_found')]);
        }

        $content = $this->getMarkdownPackage($package);

        return $this->jsonTrue(['content' => $markdown->convert($content)]);
    }

    #[PdfRoute]
    public function pdf(SymfonyInfoService $service): PdfResponse
    {
        return $this->renderPdfDocument(new SymfonyReport($this, $service));
    }

    /**
     * @phpstan-param PackageType $package
     */
    private function getMarkdownLicense(array $package): string
    {
        return $this->implodeHeader($package)
            . FileUtils::readFile((string) $package['license']);
    }

    /**
     * @phpstan-param PackageType $package
     */
    private function getMarkdownPackage(array $package): string
    {
        return $this->implodeHeader($package)
            . $this->implodeValues(Environment::PRODUCTION, $package['production'])
            . $this->implodeValues(Environment::DEVELOPMENT, $package['development']);
    }

    /**
     * @phpstan-param PackageType $package
     */
    private function implodeHeader(array $package): string
    {
        return \sprintf(
            "**%s**\n\n*Version %s - %s*\n***\n",
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
        $values = \array_map(
            static fn (string $key, string $version): string => \sprintf('- %s : `%s`', $key, $version),
            \array_keys($values),
            \array_values($values)
        );
        $title = $environment->trans($this->getTranslator());

        return \sprintf("\n\n%s\n\n%s", $title, \implode("\n", $values));
    }
}
