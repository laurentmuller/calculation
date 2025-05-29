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
use App\Report\MySqlReport;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\DatabaseInfoService;
use App\Spreadsheet\MySqlDocument;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to output MySQL information.
 */
#[IsGranted(RoleInterface::ROLE_ADMIN)]
#[Route(path: '/about/mysql', name: 'about_mysql_')]
class AboutMySqlController extends AbstractController
{
    #[GetRoute(path: '/content', name: 'content')]
    public function content(DatabaseInfoService $service): JsonResponse
    {
        $content = $this->renderView('about/mysql_content.html.twig', ['service' => $service]);

        return $this->jsonTrue(['content' => $content]);
    }

    #[ExcelRoute]
    public function excel(DatabaseInfoService $service): SpreadsheetResponse
    {
        $doc = new MySqlDocument($this, $service);

        return $this->renderSpreadsheetDocument($doc);
    }

    #[PdfRoute]
    public function pdf(DatabaseInfoService $service): PdfResponse
    {
        $report = new MySqlReport($this, $service);

        return $this->renderPdfDocument($report);
    }
}
