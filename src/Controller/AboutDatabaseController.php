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
use App\Attribute\IndexRoute;
use App\Attribute\IsAdmin;
use App\Attribute\PdfRoute;
use App\Report\DatabaseReport;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\DatabaseInfoService;
use App\Service\FontAwesomeCellService;
use App\Spreadsheet\DatabaseDocument;
use App\Traits\RenderPdfDocumentTrait;
use App\Traits\RenderSpreadsheetDocumentTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to output database information.
 */
#[IsAdmin]
#[Route(path: '/about/database', name: 'about_database_')]
class AboutDatabaseController extends AbstractController
{
    use RenderPdfDocumentTrait;
    use RenderSpreadsheetDocumentTrait;

    #[GetRoute(path: '/content', name: 'content')]
    public function content(DatabaseInfoService $service): JsonResponse
    {
        $content = $this->renderView('about/database_content.html.twig', ['service' => $service]);

        return $this->jsonTrue(['content' => $content]);
    }

    #[ExcelRoute]
    public function excel(DatabaseInfoService $service): SpreadsheetResponse
    {
        return $this->renderSpreadsheetDocument(new DatabaseDocument($this, $service));
    }

    #[IndexRoute]
    public function index(DatabaseInfoService $service): Response
    {
        return $this->render('about/about_database.html.twig', ['service' => $service]);
    }

    #[PdfRoute]
    public function pdf(DatabaseInfoService $databaseService, FontAwesomeCellService $cellService): PdfResponse
    {
        return $this->renderPdfDocument(new DatabaseReport($this, $databaseService, $cellService));
    }
}
