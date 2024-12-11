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
use App\Report\HtmlReport;
use App\Response\PdfResponse;
use App\Response\WordResponse;
use App\Service\MarkdownService;
use App\Utils\FileUtils;
use App\Word\HtmlDocument;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Abstract controller to render a Markdown file.
 *
 * @psalm-import-type TagType from MarkdownService
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

    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Get(path: '/content', name: 'content')]
    public function content(): JsonResponse
    {
        $content = $this->loadContent();

        return $this->jsonTrue(['content' => $content]);
    }

    #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
    #[Get(path: '', name: 'index')]
    public function index(): Response
    {
        $view = $this->getView();
        $content = $this->loadContent();
        $parameters = ['content' => $content];

        return $this->render($view, $parameters);
    }

    /**
     * @throws NotFoundHttpException
     */
    #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
    #[Get(path: '/pdf', name: 'pdf')]
    public function pdf(): PdfResponse
    {
        $title = $this->getTitle();
        $content = $this->loadContent();
        $report = new HtmlReport($this, $content);
        $report->setTitleTrans($title, [], true);

        return $this->renderPdfDocument($report);
    }

    /**
     * @throws NotFoundHttpException|\PhpOffice\PhpWord\Exception\Exception
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Get(path: '/word', name: 'word')]
    public function word(): WordResponse
    {
        $title = $this->getTitle();
        $content = $this->loadContent();
        $doc = new HtmlDocument($this, $content);
        $doc->setTitleTrans($title);

        return $this->renderWordDocument($doc);
    }

    /**
     * Gets the Markdown file name.
     */
    abstract protected function getFileName(): string;

    /**
     * Gets the tags to update.
     *
     * @psalm-return TagType[]
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
