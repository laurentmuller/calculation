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

use App\Interfaces\RoleInterface;
use App\Report\HtmlReport;
use App\Report\MySqlReport;
use App\Report\PhpIniReport;
use App\Report\SymfonyReport;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\DatabaseInfoService;
use App\Service\PhpInfoService;
use App\Service\SymfonyInfoService;
use App\Spreadsheet\MySqlDocument;
use App\Spreadsheet\PhpIniDocument;
use App\Spreadsheet\SymfonyDocument;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Intl\Locales;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;

/**
 * Controller for application information.
 */
#[AsController]
#[Route(path: '/about')]
class AboutController extends AbstractController
{
    /**
     * Constructor.
     *
     * @param string $appMode the application mode
     */
    public function __construct(
        #[Autowire('%app_mode%')]
        private readonly string $appMode
    ) {
    }

    /**
     * Display information about the application.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '', name: 'about')]
    public function about(): Response
    {
        return $this->renderForm('about/about.html.twig');
    }

    /**
     * Export the licence and policy pages to PDF.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/pdf', name: 'about_pdf')]
    public function aboutPdf(
        #[Autowire('%app_name%')]
        string $appName
    ): PdfResponse {
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
    #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
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
    #[IsGranted(RoleInterface::ROLE_USER)]
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
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
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
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[Route(path: '/mysql/content', name: 'about_mysql_content')]
    public function mysqlContent(DatabaseInfoService $info): JsonResponse
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
     * Exports the MySql information as Excel.
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[Route(path: '/mysql/excel', name: 'about_mysql_excel')]
    public function mysqlExcel(DatabaseInfoService $info): SpreadsheetResponse
    {
        $doc = new MySqlDocument($this, $info);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Exports the MySql information as PDF.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[Route(path: '/mysql/pdf', name: 'about_mysql_pdf')]
    public function mysqlPdf(DatabaseInfoService $info): PdfResponse
    {
        $report = new MySqlReport($this, $info);

        return $this->renderPdfDocument($report);
    }

    /**
     * Render the PHP information.
     */
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[Route(path: '/php/content', name: 'about_php_content')]
    public function phpContent(Request $request, PhpInfoService $info): JsonResponse
    {
        $parameters = [
            'phpInfo' => $info->asHtml(),
            'version' => $info->getVersion(),
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
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[Route(path: '/php/excel', name: 'about_php_excel')]
    public function phpExcel(PhpInfoService $info): SpreadsheetResponse
    {
        $content = $info->asArray();
        $version = $info->getVersion();
        $doc = new PhpIniDocument($this, $content, $version);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Exports the PHP information as PDF.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[Route(path: '/php/pdf', name: 'about_php_pdf')]
    public function phpPdf(PhpInfoService $info): PdfResponse
    {
        $content = $info->asArray();
        $version = $info->getVersion();
        $report = new PhpIniReport($this, $content, $version);

        return $this->renderPdfDocument($report);
    }

    /**
     * Display the policy page.
     */
    #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
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
    #[IsGranted(RoleInterface::ROLE_USER)]
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
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
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
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[Route(path: '/symfony/content', name: 'about_symfony_content')]
    public function symfonyContent(SymfonyInfoService $info): JsonResponse
    {
        $parameters = [
            'info' => $info,
            'locale' => $this->getLocaleName(),
        ];
        $content = $this->renderView('about/symfony_content.html.twig', $parameters);

        return $this->jsonTrue([
            'content' => $content,
        ]);
    }

    /**
     * Exports the Symfony information as Excel.
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[Route(path: '/symfony/excel', name: 'about_symfony_excel')]
    public function symfonyExcel(SymfonyInfoService $info): SpreadsheetResponse
    {
        $locale = $this->getLocaleName();
        $doc = new SymfonyDocument($this, $info, $locale, $this->appMode);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Exports the Symfony information as PDF.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[Route(path: '/symfony/pdf', name: 'about_symfony_pdf')]
    public function symfonyPdf(SymfonyInfoService $info): PdfResponse
    {
        $locale = $this->getLocaleName();
        $report = new SymfonyReport($this, $info, $locale, $this->appMode);

        return $this->renderPdfDocument($report);
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

    private function getLocaleName(): string
    {
        $locale = \Locale::getDefault();
        $name = Locales::getName($locale, 'en');

        return "$name - $locale";
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
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
