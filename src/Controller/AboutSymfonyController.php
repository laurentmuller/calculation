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
use App\Report\SymfonyReport;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\SymfonyInfoService;
use App\Spreadsheet\SymfonyDocument;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to output symfony information.
 */
#[AsController]
#[Route(path: '/about/symfony', name: 'about_symfony')]
class AboutSymfonyController extends AbstractController
{
    #[IsGranted(RoleInterface::ROLE_ADMIN)]
    #[Get(path: '/content', name: '_content')]
    public function content(SymfonyInfoService $service): JsonResponse
    {
        $content = $this->renderView('about/symfony_content.html.twig', ['service' => $service]);

        return $this->jsonTrue(['content' => $content]);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[IsGranted(RoleInterface::ROLE_ADMIN)]
    #[Get(path: '/excel', name: '_excel')]
    public function excel(SymfonyInfoService $service): SpreadsheetResponse
    {
        $doc = new SymfonyDocument($this, $service);

        return $this->renderSpreadsheetDocument($doc);
    }

    #[IsGranted(RoleInterface::ROLE_ADMIN)]
    #[Get(path: '/pdf', name: '_pdf')]
    public function pdf(SymfonyInfoService $service): PdfResponse
    {
        $doc = new SymfonyReport($this, $service);

        return $this->renderPdfDocument($doc);
    }
}
