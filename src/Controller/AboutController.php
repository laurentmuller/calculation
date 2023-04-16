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

use App\Interfaces\RoleInterface;
use App\Report\HtmlReport;
use App\Response\PdfResponse;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for application information.
 */
#[AsController]
#[Route(path: '/about')]
class AboutController extends AbstractController
{
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '', name: 'about')]
    public function index(): Response
    {
        return $this->render('about/about.html.twig');
    }

    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/pdf', name: 'about_pdf')]
    public function pdf(#[Autowire('%app_name%')] string $appName): PdfResponse
    {
        $parameters = [
            'comments' => false,
            'link' => false,
        ];
        $titleParameters = [
            '%app_name%' => $appName,
        ];
        $content = $this->renderView('about/about_content.html.twig', $parameters);
        $report = new HtmlReport($this, $content);
        $report->setTitleTrans('index.menu_info', $titleParameters, true);

        return $this->renderPdfDocument($report);
    }
}
