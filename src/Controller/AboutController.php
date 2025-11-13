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

use App\Attribute\IndexRoute;
use App\Attribute\PdfRoute;
use App\Attribute\WordRoute;
use App\Interfaces\RoleInterface;
use App\Report\HtmlReport;
use App\Response\PdfResponse;
use App\Response\WordResponse;
use App\Service\MarkdownService;
use App\Utils\FileUtils;
use App\Word\HtmlDocument;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Controller for application information.
 */
#[Route(path: '/about', name: 'about_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class AboutController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly MarkdownService $service,
        private readonly CacheInterface $cache
    ) {
    }

    #[IndexRoute]
    public function index(): Response
    {
        return $this->render('about/about.html.twig', [
            'deploy' => $this->getDeploy(),
        ]);
    }

    #[PdfRoute]
    public function pdf(): PdfResponse
    {
        $content = $this->loadContent();
        $parameters = ['%app_name%' => $this->getApplication()];
        $report = new HtmlReport($this, $content);
        $report->setTranslatedTitle('index.menu_info', $parameters, true);

        return $this->renderPdfDocument($report);
    }

    #[WordRoute]
    public function word(): WordResponse
    {
        $content = $this->loadContent();
        $parameters = ['%app_name%' => $this->getApplication()];
        $doc = new HtmlDocument($this, $content);
        $doc->setTranslatedTitle('index.menu_info', $parameters);

        return $this->renderWordDocument($doc);
    }

    private function convertFile(string $name): string
    {
        $tags = [
            ['h4', 'h6', 'bookmark bookmark-3'],
            ['h3', 'h5', 'bookmark bookmark-2'],
            ['h2', 'h4', 'bookmark bookmark-1'],
            ['h1', 'h3', 'bookmark'],
        ];
        $path = FileUtils::buildPath($this->projectDir, $name);
        $content = $this->service->convertFile($path);
        $content = $this->service->updateTags($tags, $content);

        return $this->service->addTagClass('p', 'text-justify', $content);
    }

    private function getDeploy(): int
    {
        return (int) \filemtime(FileUtils::buildPath($this->projectDir, 'composer.lock'));
    }

    private function loadContent(): string
    {
        return $this->cache->get('about-content', function (): string {
            $license = $this->convertFile(AboutLicenceController::LICENCE_FILE);
            $license = \substr($license, 0, (int) \strrpos($license, '<h4'));
            $policy = $this->convertFile(AboutPolicyController::POLICY_FILE);

            return $license . $policy;
        });
    }
}
