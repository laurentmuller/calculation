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
use App\Util\DatabaseInfo;
use App\Util\PhpInfo;
use App\Util\SymfonyInfo;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Intl\Locales;
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
     * Render the licence informations content.
     *
     * @Route("/licence/content", name="about_licence_content")
     * @IsGranted("ROLE_USER")
     */
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
     * Render the MySql informations content.
     *
     * @Route("/mysql/content", name="about_mysql_content")
     * @IsGranted("ROLE_ADMIN")
     */
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
     * Render the PHP informations content.
     *
     * @Route("/php/content", name="about_php_content")
     * @IsGranted("ROLE_ADMIN")
     */
    public function phpContent(Request $request, PhpInfo $info): JsonResponse
    {
        $parameters = [
            'cache' => $this->getCacheClass(),
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
     * Download the php.ini as JSON.
     *
     * @Route("/php/ini", name="about_php_ini")
     */
    public function phpIni(PhpInfo $info): JsonResponse
    {
        // get content
        $array = $info->asArray();

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
        $response = $this->json($array, JsonResponse::HTTP_OK, $headers);
        $response->setEncodingOptions(\JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES);

        return $response;
    }

    /**
     * Exports the php.ini as PDF.
     *
     * @Route("/php/ini/pdf", name="about_php_ini_pdf")
     */
    public function phpIniPdf(PhpInfo $info): PdfResponse
    {
        // get content
        $content = $info->asArray();

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
     * Render the policy informations content.
     *
     * @Route("/policy/content", name="about_policy_content")
     * @IsGranted("ROLE_USER")
     */
    public function policyContent(): JsonResponse
    {
        $content = $this->renderView('about/policy_content.html.twig');

        return $this->jsonTrue([
            'content' => $content,
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
     * Render Symfony informations content.
     *
     * @Route("/symfony/content", name="about_symfony_content")
     * @IsGranted("ROLE_ADMIN")
     */
    public function symfonyContent(SymfonyInfo $info): JsonResponse
    {
        $locale = \Locale::getDefault();
        $localeName = Locales::getName($locale, 'en');

        $routes = $info->getRoutes();
        $packages = $info->getPackages();

        $parameters = [
            'charset' => $info->getCharset(),
            'timezone' => $info->getTimeZone(),
            'environment' => $info->getEnvironment(),
            'version' => $info->getVersion(),

            'bundles' => $info->getBundles(),
            'runtimePackages' => $packages['runtime'] ?? [],
            'debugPackages' => $packages['debug'] ?? [],

            'runtimeRoutes' => $routes['runtime'] ?? [],
            'debugRoutes' => $routes['debug'] ?? [],

            'projectDir' => $info->getProjectDir(),
            'cacheDir' => $info->getCacheInfo(),
            'logDir' => $info->getLogInfo(),

            'endOfLife' => $info->getEndOfLifeInfo(),
            'endOfMaintenance' => $info->getEndOfMaintenanceInfo(),

            'locale' => $localeName . ' - ' . $locale,

            'debug' => $info->isDebug(),
            'apcu_enabled' => $info->isApcuLoaded(),
            'xdebug_enabled' => $info->isXdebugLoaded(),
            'zend_opcache_enabled' => $info->isZendCacheLoaded(),
        ];
        $content = $this->renderView('about/symfony_content.html.twig', $parameters);

        return $this->jsonTrue([
            'content' => $content,
        ]);
    }

    /**
     * Gets the Apache version.
     *
     * @return string|bool the Apache version on success or <code>false</code> on failure
     */
    private function getApacheVersion(Request $request)
    {
        $matches = [];
        $regex = '/Apache\/(?P<version>[1-9][0-9]*\.[0-9][^\s]*)/i';

        if (\function_exists('apache_get_version')) {
            if (($version = apache_get_version()) && \preg_match($regex, $version, $matches)) {
                return $matches['version'];
            }
        }

        $server = $request->server;
        $software = $server->get('SERVER_SOFTWARE', false);
        if ($software && false !== \stripos($software, 'apache')) {
            if (\preg_match($regex, $software, $matches)) {
                return $matches['version'];
            }
        }

        return false;
    }

    /**
     * Gets the cache adapter class.
     */
    private function getCacheClass(): string
    {
        return $this->getApplication()->getCacheClass();
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
}
