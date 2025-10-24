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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to output PHP information.
 */
#[IsGranted(RoleInterface::ROLE_ADMIN)]
#[Route(path: '/about/php', name: 'about_php_')]
class AboutPhpController extends AbstractController
{
    #[GetRoute(path: '/content', name: 'content')]
    public function content(PhpInfoService $service): JsonResponse
    {
        $parameters = [
            'phpInfo' => $service->asHtml(),
            'version' => $service->getVersion(),
            'extensions' => \implode(', ', $service->getLoadedExtensions()),
        ];
        $content = $this->renderView('about/php_content.html.twig', $parameters);

        return $this->jsonTrue(['content' => $content]);
    }

    #[ExcelRoute]
    public function excel(PhpInfoService $service): SpreadsheetResponse
    {
        return $this->renderSpreadsheetDocument(new PhpIniDocument($this, $service));
    }

    #[PdfRoute]
    public function pdf(PhpInfoService $service): PdfResponse
    {
        return $this->renderPdfDocument(new PhpIniReport($this, $service));
    }
}
