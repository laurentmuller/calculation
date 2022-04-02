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

use App\Report\LogReport;
use App\Service\LogService;
use App\Spreadsheet\LogsDocument;
use App\Table\LogTable;
use App\Traits\TableTrait;
use App\Util\FileUtils;
use App\Util\Utils;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use SlopeIt\BreadcrumbBundle\Annotation\Breadcrumb;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The log controller.
 *
 * @author Laurent Muller
 *
 * @Route("/log")
 * @IsGranted("ROLE_ADMIN")
 * @Breadcrumb({
 *     {"label" = "index.title", "route" = "homepage"}
 * })
 */
class LogController extends AbstractController
{
    use TableTrait;

    /**
     * Logs a Content Security Policy report.
     *
     * @IsGranted("ROLE_USER")
     * @Route("/csp", name="log_csp")
     */
    public function cspViolation(LoggerInterface $logger): Response
    {
        $content = (string) \file_get_contents('php://input');
        /** @psalm-var bool|array{csp-report: string[]} $data */
        $data = \json_decode($content, true);
        if (\is_array($data)) {
            $title = 'CSP Violation';
            $csp_report = $data['csp-report'];
            $context = \array_filter($csp_report, 'strlen');
            if (isset($context['document-uri'])) {
                $title .= ': ' . $context['document-uri'];
            } elseif (isset($context['source-file'])) {
                $title .= ': ' . $context['source-file'];
            }
            $logger->error($title, $context);
        }

        // no content
        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * Delete the content of the log file (if any).
     *
     * @Route("/delete", name="log_delete")
     */
    public function delete(Request $request, LogService $service, LoggerInterface $logger): Response
    {
        // get entries
        if (!$service->getEntries()) {
            $this->infoTrans('log.list.empty');

            return $this->redirectToHomePage();
        }

        // handle request
        $file = $service->getFileName();
        $form = $this->createForm();
        if ($this->handleRequestForm($request, $form)) {
            try {
                // delete file
                FileUtils::remove($file);
            } catch (\Exception $e) {
                $message = $this->trans('log.delete.error');
                $context = Utils::getExceptionContext($e);
                $logger->error($message, $context);

                return $this->renderForm('@Twig/Exception/exception.html.twig', [
                    'message' => $message,
                    'exception' => $e,
                ]);
            } finally {
                $service->clearCache();
            }

            // OK
            $this->successTrans('log.delete.success');

            return $this->redirectToHomePage();
        }

        $parameters = [
            'form' => $form,
            'file' => $file,
            'size' => FileUtils::formatSize($file),
            'entries' => FileUtils::getLinesCount($file),
            'route' => $this->getRequestString($request, 'route'),
        ];

        // display
        return $this->renderForm('log/log_delete.html.twig', $parameters);
    }

    /**
     * Export the logs to a Spreadsheet document.
     *
     * @Route("/excel", name="log_excel")
     */
    public function excel(LogService $service): Response
    {
        // get entries
        if (!$entries = $service->getEntries()) {
            $this->infoTrans('log.list.empty');

            return $this->redirectToHomePage();
        }

        $doc = new LogsDocument($this, $entries);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export to PDF the content of the log file.
     *
     * @Route("/pdf", name="log_pdf")
     */
    public function pdf(LogService $service): Response
    {
        // get entries
        if (!$entries = $service->getEntries()) {
            $this->infoTrans('log.list.empty');

            return $this->redirectToHomePage();
        }

        $doc = new LogReport($this, $entries);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Clear the log file cache.
     *
     * @Route("/refresh", name="log_refresh")
     */
    public function refresh(Request $request, LogService $service): Response
    {
        $service->clearCache();
        $route = $this->getDefaultRoute($request);

        return $this->redirectToRoute($route);
    }

    /**
     * Show properties of a log entry.
     *
     * @Route("/show/{id}", name="log_show", requirements={"id" = "\d+"})
     * @Breadcrumb({
     *     {"label" = "log.title", "route" = "log_table"},
     *     {"label" = "breadcrumb.property"},
     *     {"label" = "$item.formattedDate"}
     * })
     */
    public function show(Request $request, int $id, LogService $service): Response
    {
        if (null === $item = $service->getLog($id)) {
            $this->warningTrans('log.show.not_found');
            $route = $this->getDefaultRoute($request);

            return $this->redirectToRoute($route);
        }

        return $this->renderForm('log/log_show.html.twig', ['item' => $item]);
    }

    /**
     * Render the table view.
     *
     * @Route("", name="log_table")
     * @Breadcrumb({
     *     {"label" = "log.title"}
     * })
     */
    public function table(Request $request, LogTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'log/log_table.html.twig');
    }

    /**
     * Gets the default route name used to display the logs.
     */
    private function getDefaultRoute(Request $request): string
    {
        if (null !== $route = $this->getRequestString($request, 'route')) {
            return $route;
        }

        return 'log_table';
    }
}
