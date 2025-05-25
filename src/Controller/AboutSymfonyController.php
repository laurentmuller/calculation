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
use App\Report\SymfonyReport;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\SymfonyInfoService;
use App\Spreadsheet\SymfonyDocument;
use App\Utils\FileUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Twig\Extra\Markdown\MarkdownInterface;

/**
 * Controller to output symfony information.
 */
#[AsController]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
#[Route(path: '/about/symfony', name: 'about_symfony_')]
class AboutSymfonyController extends AbstractController
{
    #[IsGranted(RoleInterface::ROLE_ADMIN)]
    #[GetRoute(path: '/content', name: 'content')]
    public function content(SymfonyInfoService $service): JsonResponse
    {
        $content = $this->renderView('about/symfony_content.html.twig', ['service' => $service]);

        return $this->jsonTrue(['content' => $content]);
    }

    #[ExcelRoute]
    public function excel(SymfonyInfoService $service): SpreadsheetResponse
    {
        $doc = new SymfonyDocument($this, $service);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Gets the license content.
     */
    #[GetRoute(path: '/license', name: 'license')]
    public function license(
        #[MapQueryParameter]
        string $file,
        MarkdownInterface $markdown,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ): JsonResponse {
        $file = FileUtils::buildPath($projectDir, $file);
        if (!FileUtils::exists($file)) {
            return $this->jsonFalse(['message' => $this->trans('about.dialog.not_found')]);
        }
        $content = FileUtils::readFile($file);
        if ('' === $content) {
            return $this->jsonFalse(['message' => $this->trans('about.dialog.not_loaded')]);
        }

        return $this->jsonTrue([
            'content' => $markdown->convert($content),
        ]);
    }

    #[PdfRoute]
    public function pdf(SymfonyInfoService $service): PdfResponse
    {
        $doc = new SymfonyReport($this, $service);

        return $this->renderPdfDocument($doc);
    }
}
