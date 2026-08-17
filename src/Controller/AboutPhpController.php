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
use App\Report\PhpIniReport;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\FontAwesomeCellService;
use App\Service\PhpInfoService;
use App\Spreadsheet\PhpIniDocument;
use App\Traits\RenderPdfDocumentTrait;
use App\Traits\RenderSpreadsheetDocumentTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to output PHP information.
 */
#[ForAdmin]
#[Route(path: '/about/php', name: 'about_php_')]
class AboutPhpController extends AbstractController
{
    use RenderPdfDocumentTrait;
    use RenderSpreadsheetDocumentTrait;

    #[GetRoute(path: '/content', name: 'content')]
    public function content(PhpInfoService $service): JsonResponse
    {
        $content = $this->renderView('about/php_content.html.twig', [
            'info' => $service->getPhpInfo(),
        ]);

        return $this->jsonTrue(['content' => $content]);
    }

    #[ExcelRoute]
    public function excel(PhpInfoService $service): SpreadsheetResponse
    {
        return $this->renderSpreadsheetDocument(new PhpIniDocument($this, $service));
    }

    #[PdfRoute]
    public function pdf(PhpInfoService $infoService, FontAwesomeCellService $cellService): PdfResponse
    {
        return $this->renderPdfDocument(new PhpIniReport($this, $infoService, $cellService));
    }
}
