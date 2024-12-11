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
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to output policy information.
 */
#[AsController]
#[Route(path: '/about/policy', name: 'about_policy_')]
class AboutPolicyController extends AbstractController
{
    /**
     * The policy file name (markdown).
     */
    public const POLICY_FILE = 'POLICY.md';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly MarkdownService $service
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
        $content = $this->loadContent();
        $parameters = ['content' => $content];

        return $this->render('about/policy.html.twig', $parameters);
    }

    #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
    #[Get(path: '/pdf', name: 'pdf')]
    public function pdf(): PdfResponse
    {
        $content = $this->loadContent();
        $report = new HtmlReport($this, $content);
        $report->setTitleTrans('about.policy', [], true);

        return $this->renderPdfDocument($report);
    }

    /**
     * @throws NotFoundHttpException|\PhpOffice\PhpWord\Exception\Exception
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Get(path: '/word', name: 'word')]
    public function word(): WordResponse
    {
        $content = $this->loadContent();
        $doc = new HtmlDocument($this, $content);
        $doc->setTitleTrans('about.policy');

        return $this->renderWordDocument($doc);
    }

    private function loadContent(): string
    {
        $path = FileUtils::buildPath($this->projectDir, self::POLICY_FILE);
        $content = $this->service->convertFile($path, true);
        $content = $this->service->updateTag('h4', 'h6', 'bookmark bookmark-2', $content);
        $content = $this->service->updateTag('h3', 'h5', 'bookmark bookmark-1', $content);
        $content = $this->service->updateTag('h2', 'h4', 'bookmark', $content);

        return $this->service->addTagClass('p', 'text-justify', $content);
    }
}
