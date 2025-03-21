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
use App\Report\PhpIniReport;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\PhpInfoService;
use App\Spreadsheet\PhpIniDocument;
use App\Traits\ArrayTrait;
use App\Utils\StringUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to output PHP information.
 */
#[AsController]
#[Route(path: '/about/php', name: 'about_php_')]
class AboutPhpController extends AbstractController
{
    use ArrayTrait;

    #[IsGranted(RoleInterface::ROLE_ADMIN)]
    #[Get(path: '/content', name: 'content')]
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
    #[IsGranted(RoleInterface::ROLE_ADMIN)]
    #[Get(path: '/excel', name: 'excel')]
    public function excel(PhpInfoService $service): SpreadsheetResponse
    {
        $doc = new PhpIniDocument($this, $service);

        return $this->renderSpreadsheetDocument($doc);
    }

    #[IsGranted(RoleInterface::ROLE_ADMIN)]
    #[Get(path: '/pdf', name: 'pdf')]
    public function pdf(PhpInfoService $service): PdfResponse
    {
        $report = new PhpIniReport($this, $service);

        return $this->renderPdfDocument($report);
    }

    private function getApacheVersion(Request $request): bool|string
    {
        $regex = '/Apache\/(?P<version>[1-9]\d*\.\d[^\s]*)/i';
        /** @psalm-var mixed $version */
        $version = \function_exists('apache_get_version')
            ? apache_get_version()
            : $request->server->get('SERVER_SOFTWARE');
        if (\is_string($version) && StringUtils::pregMatch($regex, $version, $matches)) {
            return $matches['version'];
        }

        return false;
    }

    private function getLoadedExtensions(): string
    {
        $extensions = $this->getSorted(\array_map('strtolower', \get_loaded_extensions()));

        return \implode(', ', $extensions);
    }
}
