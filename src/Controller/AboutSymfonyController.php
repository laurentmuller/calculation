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

use App\Attribute\GetRoute;
use App\Interfaces\RoleInterface;
use App\Report\SymfonyReport;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\SymfonyInfoService;
use App\Spreadsheet\SymfonyDocument;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Intl\Locales;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to output symfony information.
 */
#[AsController]
#[Route(path: '/about/symfony')]
class AboutSymfonyController extends AbstractController
{
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[GetRoute(path: '/content', name: 'about_symfony_content')]
    public function content(SymfonyInfoService $service): JsonResponse
    {
        $parameters = [
            'service' => $service,
            'locale' => $this->getLocaleName(),
        ];
        $content = $this->renderView('about/symfony_content.html.twig', $parameters);

        return $this->jsonTrue(['content' => $content]);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[GetRoute(path: '/excel', name: 'about_symfony_excel')]
    public function excel(SymfonyInfoService $service, #[Autowire('%app_mode%')] string $appMode): SpreadsheetResponse
    {
        $doc = new SymfonyDocument($this, $service, $this->getLocaleName(), $appMode);

        return $this->renderSpreadsheetDocument($doc);
    }

    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[GetRoute(path: '/pdf', name: 'about_symfony_pdf')]
    public function pdf(SymfonyInfoService $service, #[Autowire('%app_mode%')] string $appMode): PdfResponse
    {
        $report = new SymfonyReport($this, $service, $this->getLocaleName(), $appMode);

        return $this->renderPdfDocument($report);
    }

    private function getLocaleName(): string
    {
        $locale = \Locale::getDefault();
        $name = Locales::getName($locale, 'en');

        return "$name - $locale";
    }
}
