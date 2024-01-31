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

use App\Attribute\Get;
use App\Interfaces\RoleInterface;
use App\Report\MySqlReport;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\DatabaseInfoService;
use App\Spreadsheet\MySqlDocument;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to output MySQL information.
 */
#[AsController]
#[Route(path: '/about/mysql')]
class AboutMySqlController extends AbstractController
{
    #[IsGranted(RoleInterface::ROLE_ADMIN)]
    #[Get(path: '/content', name: 'about_mysql_content')]
    public function content(DatabaseInfoService $service): JsonResponse
    {
        $content = $this->renderView('about/mysql_content.html.twig', ['service' => $service]);

        return $this->jsonTrue(['content' => $content]);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[IsGranted(RoleInterface::ROLE_ADMIN)]
    #[Get(path: '/excel', name: 'about_mysql_excel')]
    public function excel(DatabaseInfoService $service): SpreadsheetResponse
    {
        $doc = new MySqlDocument($this, $service);

        return $this->renderSpreadsheetDocument($doc);
    }

    #[IsGranted(RoleInterface::ROLE_ADMIN)]
    #[Get(path: '/pdf', name: 'about_mysql_pdf')]
    public function pdf(DatabaseInfoService $service): PdfResponse
    {
        $report = new MySqlReport($this, $service);

        return $this->renderPdfDocument($report);
    }
}
