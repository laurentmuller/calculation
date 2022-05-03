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

use App\Report\HtmlReport;
use App\Report\MySqlReport;
use App\Report\PhpIniReport;
use App\Response\PdfResponse;
use App\Util\DatabaseInfo;
use App\Util\PhpInfo;
use App\Util\SymfonyInfo;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Intl\Locales;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for application information.
 */
#[AsController]
#[Route(path: '/about')]
class AboutController extends AbstractController
{
    /**
     * Display information about the application.
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '', name: 'about')]
    public function about(): Response
    {
        return $this->renderForm('about/about.html.twig');
    }

    /**
     * Export the licence and policy pages to PDF.
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/pdf', name: 'about_pdf')]
    public function aboutPdf(string $appName): PdfResponse
    {
        $templateParameters = [
            'comments' => false,
            'link' => false,
        ];
        $titleParameters = [
            '%app_name%' => $appName,
        ];

        return $this->outputReport('about/about_content.html.twig', $templateParameters, 'index.menu_info', $titleParameters);
    }

    /**
     * Display the licence page.
     */
    #[IsGranted('PUBLIC_ACCESS')]
    #[Route(path: '/licence ', name: 'about_licence')]
    public function licence(): Response
    {
        return $this->renderForm('about/licence.html.twig', [
            'link' => true,
        ]);
    }

    /**
     * Render the licence information.
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/licence/content', name: 'about_licence_content')]
    public function licenceContent(): JsonResponse
    {
        $content = $this->renderView('about/licence_content.html.twig');

        return $this->jsonTrue([
            'content' => $content,
        ]);
    }

    /**
     * Export the licence page to PDF.
     */
    #[IsGranted('PUBLIC_ACCESS')]
    #[Route(path: '/licence/pdf', name: 'about_licence_pdf')]
    public function licencePdf(): PdfResponse
    {
        $templateParameters = [
            'link' => false,
        ];

        return $this->outputReport('about/licence_content.html.twig', $templateParameters, 'about.licence');
    }

    /**
     * Render the MySql information.
     */
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(path: '/mysql/content', name: 'about_mysql_content')]
    public function mysqlContent(DatabaseInfo $info): JsonResponse
    {
        $parameters = [
            'version' => $info->getVersion(),
            'database' => $info->getDatabase(),
            'configuration' => $info->getConfiguration(),
        ];
        $content = $this->renderView('about/mysql_content.html.twig', $parameters);

        return $this->jsonTrue([
            'content' => $content,
        ]);
    }

    /**
     * Exports the MySql information as PDF.
     */
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(path: '/mysql/pdf', name: 'about_mysql_pdf')]
    public function mysqlPdf(DatabaseInfo $info): PdfResponse
    {
        $report = new MySqlReport($this, $info);

        return $this->renderPdfDocument($report);
    }

    /**
     * Render the PHP information.
     */
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(path: '/php/content', name: 'about_php_content')]
    public function phpContent(Request $request, PhpInfo $info): JsonResponse
    {
        $parameters = [
            'phpInfo' => $info->asHtml(),
            'extensions' => $this->getLoadedExtensions(),
            'apache' => $this->getApacheVersion($request),
        ];
        $content = $this->renderView('about/php_content.html.twig', $parameters);

        return $this->jsonTrue([
            'content' => $content,
        ]);
    }

    /**
     * Exports the PHP information as PDF.
     */
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(path: '/php/pdf', name: 'about_php_pdf')]
    public function phpPdf(PhpInfo $info): PdfResponse
    {
        $content = $info->asArray();
        $version = $info->getVersion();
        $report = new PhpIniReport($this, $content, $version);

        return $this->renderPdfDocument($report);
    }

    /**
     * Display the policy page.
     */
    #[IsGranted('PUBLIC_ACCESS')]
    #[Route(path: '/policy', name: 'about_policy')]
    public function policy(): Response
    {
        return $this->renderForm('about/policy.html.twig', [
            'comments' => false,
            'link' => true,
        ]);
    }

    /**
     * Render the policy information.
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/policy/content', name: 'about_policy_content')]
    public function policyContent(): JsonResponse
    {
        $content = $this->renderView('about/policy_content.html.twig', [
            'comments' => true,
            'link' => false,
        ]);

        return $this->jsonTrue(['content' => $content]);
    }

    /**
     * Export the policy to PDF.
     */
    #[IsGranted('PUBLIC_ACCESS')]
    #[Route(path: '/policy/pdf', name: 'about_policy_pdf')]
    public function policyPdf(): PdfResponse
    {
        $templateParameters = [
            'comments' => false,
            'link' => false,
        ];

        return $this->outputReport('about/policy_content.html.twig', $templateParameters, 'about.policy');
    }

    /**
     * Render Symfony information.
     */
    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route(path: '/symfony/content', name: 'about_symfony_content')]
    public function symfonyContent(SymfonyInfo $info): JsonResponse
    {
        $locale = \Locale::getDefault();
        $localeName = Locales::getName($locale, 'en');
        $parameters = [
            'info' => $info,
            'locale' => $localeName . ' - ' . $locale,
        ];
        $content = $this->renderView('about/symfony_content.html.twig', $parameters);

        return $this->jsonTrue([
            'content' => $content,
        ]);
    }

    /**
     * Gets the Apache version.
     */
    private function getApacheVersion(Request $request): bool|string
    {
        $matches = [];
        $regex = '/Apache\/(?P<version>[1-9]\d*\.\d[^\s]*)/i';

        if (\function_exists('apache_get_version') && (($version = apache_get_version()) && \preg_match($regex, $version, $matches))) {
            return $matches['version'];
        }

        $server = $request->server;
        /** @psalm-var string|null $software */
        $software = $server->get('SERVER_SOFTWARE');
        if ($software && false !== \stripos($software, 'apache') && \preg_match($regex, $software, $matches)) {
            return $matches['version'];
        }

        return false;
    }

    /**
     * Returns a string with the names of all modules compiled and loaded.
     *
     * @return string all the modules names
     */
    private function getLoadedExtensions(): string
    {
        $extensions = \array_map('strtolower', \get_loaded_extensions());
        \sort($extensions);

        return \implode(', ', $extensions);
    }

    private function outputReport(string $template, array $templateParameters, ?string $title = null, array $titleParameters = []): PdfResponse
    {
        // get content
        $content = $this->renderView($template, $templateParameters);

        // create report
        $report = new HtmlReport($this);
        $report->setContent($content);

        // title
        if ($title) {
            $report->setTitleTrans($title, $titleParameters, true);
        }

        return $this->renderPdfDocument($report);
    }
}
