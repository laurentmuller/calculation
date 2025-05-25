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
use App\Interfaces\RoleInterface;
use App\Report\PhpIniReport;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\PhpInfoService;
use App\Spreadsheet\PhpIniDocument;
use App\Traits\ArrayTrait;
use App\Utils\StringUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to output PHP information.
 */
#[AsController]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
#[Route(path: '/about/php', name: 'about_php_')]
class AboutPhpController extends AbstractController
{
    use ArrayTrait;

    #[GetRoute(path: '/content', name: 'content')]
    public function content(PhpInfoService $service): JsonResponse
    {
        $parameters = [
            'phpInfo' => $service->asHtml(),
            'version' => $service->getVersion(),
            'extensions' => $this->getLoadedExtensions(),
            'apache' => $this->getApacheVersion(),
        ];
        $content = $this->renderView('about/php_content.html.twig', $parameters);

        return $this->jsonTrue(['content' => $content]);
    }

    #[ExcelRoute]
    public function excel(PhpInfoService $service): SpreadsheetResponse
    {
        $doc = new PhpIniDocument($this, $service);

        return $this->renderSpreadsheetDocument($doc);
    }

    #[PdfRoute]
    public function pdf(PhpInfoService $service): PdfResponse
    {
        $report = new PhpIniReport($this, $service);

        return $this->renderPdfDocument($report);
    }

    private function getApacheVersion(): string|false
    {
        if (!\function_exists('apache_get_version')) {
            return false;
        }

        $version = apache_get_version();
        $regex = '/Apache\/(?<version>[1-9]\d*[\.?\d]*)/i';
        if (!\is_string($version) || !StringUtils::pregMatch($regex, $version, $matches)) {
            return false;
        }

        return $matches['version'];
    }

    private function getLoadedExtensions(): string
    {
        $extensions = $this->getSorted(\array_map(strtolower(...), \get_loaded_extensions()));

        return \implode(', ', $extensions);
    }
}
