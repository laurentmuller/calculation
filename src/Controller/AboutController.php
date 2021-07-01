<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Controller;

use App\Pdf\PdfResponse;
use App\Report\HtmlReport;
use App\Report\PhpIniReport;
use App\Util\SymfonyUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Controller for application informations.
 *
 * @author Laurent Muller
 *
 * @Route("/about")
 */
class AboutController extends AbstractController
{
    /**
     * Display informations about the application.
     *
     * @Route("", name="about")
     * @IsGranted("ROLE_USER")
     */
    public function about(): Response
    {
        return $this->renderForm('about/about.html.twig', [
            'app_customer' => $this->getApplication()->getCustomerName(),
            'app_customer_url' => $this->getApplication()->getCustomerUrl(),
            'app_home_url' => $this->getHomeUrl(),
            'link' => false,
        ]);
    }

    /**
     * Export the licence and policy pages to PDF.
     *
     * @Route("/pdf", name="about_pdf")
     */
    public function aboutPdf(): PdfResponse
    {
        // content
        $content = $this->renderView('about/about_content.html.twig', [
            'app_home_url' => $this->getHomeUrl(),
            'display_date' => true,
            'licence_date' => false,
            'policy_date' => true,
            'link' => false,
        ]);

        // title parameters
        $parameters = ['%app_name%' => $this->getStringParameter('app_name')];

        // create report
        $report = new HtmlReport($this);
        $report->setContent($content)->setTitleTrans('index.menu_info', $parameters, true);

        // render
        return $this->renderPdfDocument($report);
    }

    /**
     * Display the licence page.
     *
     * @Route("/licence ", name="about_licence")
     */
    public function licence(): Response
    {
        return $this->renderForm('about/licence.html.twig', [
            'app_home_url' => $this->getHomeUrl(),
            'licence_date' => true,
            'link' => true,
        ]);
    }

    /**
     * Export the licence page to PDF.
     *
     * @Route("/licence/pdf", name="about_licence_pdf")
     */
    public function licencePdf(): PdfResponse
    {
        // get content
        $ontent = $this->renderView('about/licence_content.html.twig', [
            'app_home_url' => $this->getHomeUrl(),
            'licence_date' => true,
            'link' => false,
        ]);

        // create report
        $report = new HtmlReport($this);
        $report->setContent($ontent)->setTitleTrans('about.licence');

        // render
        return $this->renderPdfDocument($report);
    }

    /**
     * Download the php.ini as JSON.
     *
     * @Route("/php/ini", name="about_php_ini")
     */
    public function phpIni(): JsonResponse
    {
        // get content
        $array = SymfonyUtils::getPhpInfoArray();

        // create headers
        $disposition = HeaderUtils::makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'php_info.json'
        );
        $headers = [
            'Content-Disposition' => $disposition,
            //'Content-Type' => 'application/x-download',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ];

        // create response
        $response = new JsonResponse($array, JsonResponse::HTTP_OK, $headers);
        $response->setEncodingOptions(\JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);

        return $response;
    }

    /**
     * Exports the php.ini as PDF.
     *
     * @Route("/php/ini/pdf", name="about_php_ini_pdf")
     */
    public function phpIniPdf(): PdfResponse
    {
        // get content
        $content = SymfonyUtils::getPhpInfoArray();

        // create report
        $report = new PhpIniReport($this);
        $report->setContent($content);

        // render
        return $this->renderPdfDocument($report);
    }

    /**
     * Display the private policy page.
     *
     * @Route("/policy", name="about_policy")
     */
    public function policy(): Response
    {
        return $this->renderForm('about/policy.html.twig', [
            'app_home_url' => $this->getHomeUrl(),
            'policy_date' => true,
            'link' => true,
        ]);
    }

    /**
     * Export the policy page to PDF.
     *
     * @Route("/policy/pdf", name="about_policy_pdf")
     */
    public function policyPdf(): PdfResponse
    {
        // get content
        $content = $this->renderView('about/policy_content.html.twig', [
            'app_home_url' => $this->getHomeUrl(),
            'policy_date' => true,
            'link' => false,
        ]);

        // create report
        $report = new HtmlReport($this);
        $report->setContent($content)->setTitleTrans('about.policy', [], true);

        // render
        return $this->renderPdfDocument($report);
    }

    /**
     * Gets the home page URL.
     */
    private function getHomeUrl(): string
    {
        $url = $this->generateUrl(self::HOME_PAGE, [], UrlGeneratorInterface::ABSOLUTE_URL);
        if (false !== $pos = \stripos($url, '/web')) {
            $url = \substr($url, 0, $pos);
        }
        if (false !== $pos = \stripos($url, '/app_dev.php')) {
            $url = \substr($url, 0, $pos);
        }

        return \rtrim($url, '/');
    }
}
