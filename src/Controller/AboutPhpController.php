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
use App\Report\PhpIniReport;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\PhpInfoService;
use App\Spreadsheet\PhpIniDocument;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to output PHP information.
 */
#[AsController]
#[Route(path: '/about/php')]
class AboutPhpController extends AbstractController
{
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[GetRoute(path: '/content', name: 'about_php_content')]
    public function content(Request $request, PhpInfoService $service): JsonResponse
    {
        $parameters = [
            'phpInfo' => $service->asHtml(),
            'version' => $service->getVersion(),
            'extensions' => $this->getLoadedExtensions(),
            'apache' => $this->getApacheVersion($request),
        ];
        $content = $this->renderView('about/php_content.html.twig', $parameters);

        return $this->jsonTrue(['content' => $content]);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[GetRoute(path: '/excel', name: 'about_php_excel')]
    public function excel(PhpInfoService $service): SpreadsheetResponse
    {
        $doc = new PhpIniDocument($this, $service);

        return $this->renderSpreadsheetDocument($doc);
    }

    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[GetRoute(path: '/pdf', name: 'about_php_pdf')]
    public function pdf(PhpInfoService $service): PdfResponse
    {
        $report = new PhpIniReport($this, $service);

        return $this->renderPdfDocument($report);
    }

    private function getApacheVersion(Request $request): bool|string
    {
        $matches = [];
        $regex = '/Apache\/(?P<version>[1-9]\d*\.\d[^\s]*)/i';
        if (\function_exists('apache_get_version')) {
            $version = apache_get_version();
            if (\is_string($version) && \preg_match($regex, $version, $matches)) {
                return $matches['version'];
            }
        }

        /** @psalm-var string|null $software */
        $software = $request->server->get('SERVER_SOFTWARE');
        if (null !== $software && false !== \stripos($software, 'apache') && \preg_match($regex, $software, $matches)) {
            return $matches['version'];
        }

        return false;
    }

    private function getLoadedExtensions(): string
    {
        $extensions = \array_map('strtolower', \get_loaded_extensions());
        \sort($extensions);

        return \implode(', ', $extensions);
    }
}
