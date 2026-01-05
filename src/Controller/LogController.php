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

use App\Attribute\ExcelRoute;
use App\Attribute\ForAdmin;
use App\Attribute\GetPostRoute;
use App\Attribute\GetRoute;
use App\Attribute\IndexRoute;
use App\Attribute\PdfRoute;
use App\Attribute\ShowEntityRoute;
use App\Entity\Log;
use App\Enums\FlashType;
use App\Model\LogFile;
use App\Report\LogsReport;
use App\Resolver\DataQueryValueResolver;
use App\Service\FontAwesomeService;
use App\Service\LogService;
use App\Spreadsheet\LogsDocument;
use App\Table\DataQuery;
use App\Table\LogTable;
use App\Traits\TableTrait;
use App\Utils\FileUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The log controller.
 */
#[ForAdmin]
#[Route(path: '/log', name: 'log_')]
class LogController extends AbstractController
{
    use TableTrait;

    /**
     * Delete the content of the log file (if any).
     */
    #[GetPostRoute(path: '/delete', name: 'delete')]
    public function delete(Request $request, LogService $service, LoggerInterface $logger): Response
    {
        $file = $this->getLogFile($service)?->getFile();
        if (null === $file || FileUtils::empty($file)) {
            return $this->getEmptyResponse();
        }

        $form = $this->createForm(FormType::class)
            ->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if ($this->setEmptyFile($file)) {
                    return $this->redirectToHomePage('log.delete.success');
                }

                return $this->redirectToHomePage('log.delete.error', [], FlashType::DANGER);
            } catch (\Exception $e) {
                return $this->renderFormException('log.delete.error', $e, $logger);
            } finally {
                $service->clearCache();
            }
        }

        $parameters = [
            'form' => $form,
            'file' => $this->getRelativePath($file),
            'size' => FileUtils::formatSize($file),
            'entries' => FileUtils::getLinesCount($file),
            'route' => $this->getDefaultRoute($request),
        ];

        return $this->render('log/log_delete.html.twig', $parameters);
    }

    /**
     * Download the file.
     */
    #[GetRoute(path: '/download', name: 'download')]
    public function download(LogService $service): Response
    {
        if (!$service->isFileValid()) {
            return $this->redirectToHomePage('log.download.error', [], FlashType::WARNING);
        }

        return $this->file($service->getFileName());
    }

    /**
     * Export the logs to a Spreadsheet document.
     */
    #[ExcelRoute]
    public function excel(LogService $service): Response
    {
        $logFile = $this->getLogFile($service);
        if (!$logFile instanceof LogFile) {
            return $this->getEmptyResponse();
        }

        return $this->renderSpreadsheetDocument(new LogsDocument($this, $logFile));
    }

    /**
     * Render the table view.
     */
    #[IndexRoute]
    public function index(
        LogTable $table,
        LoggerInterface $logger,
        #[ValueResolver(DataQueryValueResolver::class)]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'log/log_table.html.twig');
    }

    /**
     * Export to PDF the content of the log file.
     */
    #[PdfRoute]
    public function pdf(
        LogService $logService,
        FontAwesomeService $service
    ): Response {
        $logFile = $this->getLogFile($logService);
        if (!$logFile instanceof LogFile) {
            return $this->getEmptyResponse();
        }

        return $this->renderPdfDocument(new LogsReport($this, $logFile, $service));
    }

    /**
     * Clear the log file cache.
     */
    #[GetRoute(path: '/refresh', name: 'refresh')]
    public function refresh(Request $request, LogService $service): Response
    {
        $service->clearCache();

        return $this->redirectToDefaultRoute($request);
    }

    /**
     * Show properties of a log entry.
     */
    #[ShowEntityRoute]
    public function show(Request $request, int $id, LogService $service): Response
    {
        $item = $service->getLog($id);
        if (!$item instanceof Log) {
            $this->warningTrans('log.show.not_found');

            return $this->redirectToDefaultRoute($request);
        }

        return $this->render('log/log_show.html.twig', ['item' => $item]);
    }

    /**
     * Gets the default route name used to display the logs.
     */
    private function getDefaultRoute(Request $request): string
    {
        return $this->getRequestString($request, 'route', 'log_index');
    }

    private function getEmptyResponse(): RedirectResponse
    {
        return $this->redirectToHomePage('log.list.empty', [], FlashType::INFO);
    }

    private function getLogFile(LogService $service): ?LogFile
    {
        $logFile = $service->getLogFile();
        if (!$logFile instanceof LogFile || $logFile->isEmpty()) {
            return null;
        }

        return $logFile;
    }

    private function redirectToDefaultRoute(Request $request): RedirectResponse
    {
        $route = $this->getDefaultRoute($request);

        return $this->redirectToRoute($route);
    }

    private function setEmptyFile(string $file): bool
    {
        $stream = \fopen($file, 'w');

        return \is_resource($stream) && \fclose($stream);
    }
}
