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
use App\Attribute\IndexRoute;
use App\Attribute\IsAdmin;
use App\Attribute\PdfRoute;
use App\Report\PhpIniReport;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\FontAwesomeCellService;
use App\Service\PhpInfoService;
use App\Spreadsheet\PhpIniDocument;
use App\Traits\RenderPdfDocumentTrait;
use App\Traits\RenderSpreadsheetDocumentTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to output PHP information.
 */
#[IsAdmin]
#[Route(path: '/about/php', name: 'about_php_')]
class AboutPhpController extends AbstractController
{
    use RenderPdfDocumentTrait;
    use RenderSpreadsheetDocumentTrait;

    #[ExcelRoute]
    public function excel(PhpInfoService $service): SpreadsheetResponse
    {
        return $this->renderSpreadsheetDocument(new PhpIniDocument($this, $service));
    }

    #[IndexRoute]
    public function index(PhpInfoService $service): Response
    {
        return $this->render('about/about_php.html.twig', ['info' => $service->getPhpInfo()]);
    }

    #[PdfRoute]
    public function pdf(PhpInfoService $infoService, FontAwesomeCellService $cellService): PdfResponse
    {
        return $this->renderPdfDocument(new PhpIniReport($this, $infoService, $cellService));
    }
}
