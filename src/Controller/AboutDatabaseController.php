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
use App\Report\DatabaseReport;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\DatabaseInfoService;
use App\Spreadsheet\DatabaseDocument;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to output database information.
 */
#[IsGranted(RoleInterface::ROLE_ADMIN)]
#[Route(path: '/about/database', name: 'about_database_')]
class AboutDatabaseController extends AbstractController
{
    #[GetRoute(path: '/content', name: 'content')]
    public function content(DatabaseInfoService $service): JsonResponse
    {
        $content = $this->renderView('about/database_content.html.twig', ['service' => $service]);

        return $this->jsonTrue(['content' => $content]);
    }

    #[ExcelRoute]
    public function excel(DatabaseInfoService $service): SpreadsheetResponse
    {
        $doc = new DatabaseDocument($this, $service);

        return $this->renderSpreadsheetDocument($doc);
    }

    #[PdfRoute]
    public function pdf(DatabaseInfoService $service): PdfResponse
    {
        $report = new DatabaseReport($this, $service);

        return $this->renderPdfDocument($report);
    }
}
