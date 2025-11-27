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

use App\Attribute\ForPublicAccess;
use App\Attribute\ForUser;
use App\Attribute\GetRoute;
use App\Attribute\IndexRoute;
use App\Attribute\PdfRoute;
use App\Attribute\WordRoute;
use App\Report\HtmlReport;
use App\Response\PdfResponse;
use App\Response\WordResponse;
use App\Service\MarkdownService;
use App\Utils\FileUtils;
use App\Word\HtmlDocument;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Abstract controller to render a Markdown file.
 *
 * @phpstan-import-type TagType from MarkdownService
 */
abstract class AbstractAboutController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly MarkdownService $service,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Gets the HTML content as a JSON response.
     */
    #[ForUser]
    #[GetRoute(path: '/content', name: 'content')]
    public function content(): JsonResponse
    {
        $content = $this->loadContent();

        return $this->jsonTrue(['content' => $content]);
    }

    /**
     * Render a view with the HTML content.
     */
    #[ForPublicAccess]
    #[IndexRoute]
    public function index(): Response
    {
        $view = $this->getView();
        $content = $this->loadContent();
        $parameters = ['content' => $content];

        return $this->render($view, $parameters);
    }

    /**
     * Export the HTML content to a Portable Document Format (*.pdf) file.
     */
    #[ForPublicAccess]
    #[PdfRoute]
    public function pdf(): PdfResponse
    {
        $title = $this->getTitle();
        $content = $this->loadContent();
        $report = new HtmlReport($this, $content);
        $report->setTranslatedTitle($title, isUTF8: true);

        return $this->renderPdfDocument($report);
    }

    /**
     * Export the HTML content to a Word 2007 (.docx) document file.
     */
    #[ForPublicAccess]
    #[WordRoute]
    public function word(): WordResponse
    {
        $title = $this->getTitle();
        $content = $this->loadContent();
        $doc = new HtmlDocument($this, $content);
        $doc->setTranslatedTitle($title);

        return $this->renderWordDocument($doc);
    }

    /**
     * Gets the Markdown file name; relative to the project directory.
     */
    abstract protected function getFileName(): string;

    /**
     * Gets the tags to update.
     *
     * @phpstan-return TagType[]
     */
    abstract protected function getTags(): array;

    /**
     * Gets the document title to translate.
     */
    abstract protected function getTitle(): string;

    /**
     * Gets the view name to render.
     */
    abstract protected function getView(): string;

    /**
     * Load the Markdown file and convert the content to HTML.
     */
    private function loadContent(): string
    {
        $fileName = $this->getFileName();

        return $this->cache->get($fileName, function () use ($fileName): string {
            $tags = $this->getTags();
            $path = FileUtils::buildPath($this->projectDir, $fileName);
            $content = $this->service->convertFile($path);
            $content = $this->service->removeTitle($content);
            $content = $this->service->updateTags($tags, $content);

            return $this->service->addTagClass('p', 'text-justify', $content);
        });
    }
}
