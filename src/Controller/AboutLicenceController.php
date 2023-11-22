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

use App\Attribute\GetRoute;
use App\Interfaces\RoleInterface;
use App\Report\HtmlReport;
use App\Response\PdfResponse;
use App\Response\WordResponse;
use App\Word\HtmlDocument;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to output licence information.
 */
#[AsController]
#[Route(path: '/about/licence')]
class AboutLicenceController extends AbstractController
{
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[GetRoute(path: '/content', name: 'about_licence_content')]
    public function content(): JsonResponse
    {
        $parameters = [
            'comments' => true,
            'link' => false,
        ];
        $content = $this->renderView('about/licence_content.html.twig', $parameters);

        return $this->jsonTrue(['content' => $content]);
    }

    #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
    #[GetRoute(path: '', name: 'about_licence')]
    public function index(): Response
    {
        $parameters = [
            'comments' => true,
            'link' => true,
        ];

        return $this->render('about/licence.html.twig', $parameters);
    }

    #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
    #[GetRoute(path: '/pdf', name: 'about_licence_pdf')]
    public function pdf(): PdfResponse
    {
        $parameters = [
            'comments' => false,
            'link' => false,
        ];
        $content = $this->renderView('about/licence_content.html.twig', $parameters);
        $report = new HtmlReport($this, $content);
        $report->setTitleTrans('about.licence', [], true);

        return $this->renderPdfDocument($report);
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[GetRoute(path: '/word', name: 'about_licence_word')]
    public function word(): WordResponse
    {
        $parameters = [
            'comments' => false,
            'link' => false,
        ];
        $content = $this->renderView('about/licence_content.html.twig', $parameters);
        $doc = new HtmlDocument($this, $content);
        $doc->setTitleTrans('about.licence');

        return $this->renderWordDocument($doc);
    }
}
