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
use App\Pdf\Html\HtmlTag;
use App\Report\HtmlReport;
use App\Response\PdfResponse;
use App\Response\WordResponse;
use App\Service\MarkdownService;
use App\Utils\FileUtils;
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
    public function pdf(
        #[Autowire('%app_name%')]
        string $appName,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
        MarkdownService $service
    ): PdfResponse {
        $content = $this->loadContent($projectDir, $service);
        $parameters = ['%app_name%' => $appName];
        $report = new HtmlReport($this, $content);
        $report->setTitleTrans('index.menu_info', $parameters, true);

        return $this->renderPdfDocument($report);
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    #[Get(path: '/word', name: 'word')]
    public function word(
        #[Autowire('%app_name%')]
        string $appName,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
        MarkdownService $service
    ): WordResponse {
        $content = $this->loadContent($projectDir, $service);
        $parameters = ['%app_name%' => $appName];
        $doc = new HtmlDocument($this, $content);
        $doc->setTitleTrans('index.menu_info', $parameters);

        return $this->renderWordDocument($doc);
    }

    private function convertFile(string $projectDir, MarkdownService $service, string $name): string
    {
        $path = FileUtils::buildPath($projectDir, $name);
        $content = $service->convertFile($path);
        $content = $service->updateTag('h4', 'h6', 'bookmark bookmark-3', $content);
        $content = $service->updateTag('h3', 'h5', 'bookmark bookmark-2', $content);
        $content = $service->updateTag('h2', 'h4', 'bookmark bookmark-1', $content);
        $content = $service->updateTag('h1', 'h3', 'bookmark', $content);

        return $service->addTagClass('p', 'text-justify', $content);
    }

    private function loadContent(string $projectDir, MarkdownService $service): string
    {
        $license = $this->convertFile($projectDir, $service, AboutLicenceController::LICENCE_FILE);
        $policy = $this->convertFile($projectDir, $service, AboutPolicyController::POLICY_FILE);

        return \sprintf('%s<p class="%s" />%s', $license, HtmlTag::PAGE_BREAK->value, $policy);
    }
}
