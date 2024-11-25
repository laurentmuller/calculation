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
use App\Enums\Environment;
use App\Interfaces\RoleInterface;
use App\Report\HtmlReport;
use App\Response\PdfResponse;
use App\Response\WordResponse;
use App\Word\HtmlDocument;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for application information.
 */
#[AsController]
#[Route(path: '/about', name: 'about_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class AboutController extends AbstractController
{
    #[Get(path: '', name: 'index')]
    public function index(
        #[Autowire('%kernel.environment%')]
        string $app_env,
        #[Autowire('%app_mode%')]
        string $app_mode
    ): Response {
        return $this->render('about/about.html.twig', [
            'env' => Environment::from($app_env),
            'mode' => Environment::from($app_mode),
        ]);
    }

    #[Get(path: '/pdf', name: 'pdf')]
    public function pdf(#[Autowire('%app_name%')] string $appName): PdfResponse
    {
        $parameters = [
            'link' => false,
            'comments' => false,
        ];
        $titleParameters = [
            '%app_name%' => $appName,
        ];
        $content = $this->renderView('about/about_content.html.twig', $parameters);
        $report = new HtmlReport($this, $content);
        $report->setTitleTrans('index.menu_info', $titleParameters, true);

        return $this->renderPdfDocument($report);
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    #[Get(path: '/word', name: 'word')]
    public function word(#[Autowire('%app_name%')] string $appName): WordResponse
    {
        $parameters = [
            'link' => false,
            'comments' => false,
        ];
        $titleParameters = [
            '%app_name%' => $appName,
        ];

        $content = $this->renderView('about/about_content.html.twig', $parameters);
        $doc = new HtmlDocument($this, $content);
        $doc->setTitleTrans('index.menu_info', $titleParameters);

        return $this->renderWordDocument($doc);
    }
}
