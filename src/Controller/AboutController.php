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

use App\Attribute\ForUser;
use App\Attribute\IndexRoute;
use App\Attribute\PdfRoute;
use App\Attribute\WordRoute;
use App\Report\HtmlReport;
use App\Response\PdfResponse;
use App\Response\WordResponse;
use App\Service\EnvironmentService;
use App\Service\MarkdownService;
use App\Word\HtmlDocument;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Controller for application information.
 */
#[ForUser]
#[Route(path: '/about', name: 'about_')]
class AboutController extends AbstractController
{
    private const array TAGS = [
        ['h4', 'h6', 'bookmark bookmark-3'],
        ['h3', 'h5', 'bookmark bookmark-2'],
        ['h2', 'h4', 'bookmark bookmark-1'],
        ['h1', 'h3', 'bookmark'],
        ['p', 'p', 'text-justify'],
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly MarkdownService $service,
        private readonly EnvironmentService $environment,
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
        $report = new HtmlReport($this, $content);
        $parameters = ['%app_name%' => $this->getApplicationService()->getName()];
        $report->setTranslatedTitle('index.menu_info', $parameters, true);

        return $this->renderPdfDocument($report);
    }

    #[WordRoute]
    public function word(): WordResponse
    {
        $content = $this->loadContent();
        $doc = new HtmlDocument($this, $content);
        $parameters = ['%app_name%' => $this->getApplicationService()->getName()];
        $doc->setTranslatedTitle('index.menu_info', $parameters);

        return $this->renderWordDocument($doc);
    }

    private function getDeploy(): int
    {
        return $this->cache->get('about-controller-deploy', function (): int {
            $file = $this->environment->isProduction() ? '.htdeployment' : 'composer.lock';

            return (int) \filemtime(Path::join($this->projectDir, $file));
        });
    }

    private function loadContent(): string
    {
        return $this->cache->get('about-controller-content', function (): string {
            $license = $this->processFile(AboutLicenceController::LICENCE_FILE);
            $license = \substr($license, 0, (int) \strrpos($license, '<h4'));
            $policy = $this->processFile(AboutPolicyController::POLICY_FILE);

            return $license . $policy;
        });
    }

    private function processFile(string $name): string
    {
        return $this->service->processFile(Path::join($this->projectDir, $name), self::TAGS, false);
    }
}
