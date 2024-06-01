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
use App\Word\HtmlDocument;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
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
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Get(path: '/content', name: 'content')]
    public function content(): JsonResponse
    {
        $parameters = [
            'comments' => true,
            'link' => false,
        ];
        $content = $this->renderView('about/policy_content.html.twig', $parameters);

        return $this->jsonTrue(['content' => $content]);
    }

    #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
    #[Get(path: '', name: 'index')]
    public function index(): Response
    {
        $parameters = [
            'comments' => true,
            'link' => true,
        ];

        return $this->render('about/policy.html.twig', $parameters);
    }

    #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
    #[Get(path: '/pdf', name: 'pdf')]
    public function pdf(): PdfResponse
    {
        $parameters = [
            'comments' => false,
            'link' => false,
        ];
        $content = $this->renderView('about/policy_content.html.twig', $parameters);
        $report = new HtmlReport($this, $content);
        $report->setTitleTrans('about.policy', [], true);

        return $this->renderPdfDocument($report);
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Get(path: '/word', name: 'word')]
    public function word(): WordResponse
    {
        $parameters = [
            'comments' => false,
            'link' => false,
        ];
        $content = $this->renderView('about/policy_content.html.twig', $parameters);
        $doc = new HtmlDocument($this, $content);
        $doc->setTitleTrans('about.policy');

        return $this->renderWordDocument($doc);
    }
}
