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

use App\Entity\Log;
use App\Enums\FlashType;
use App\Interfaces\RoleInterface;
use App\Model\LogFile;
use App\Report\LogsReport;
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
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The log controller.
 */
#[AsController]
#[Route(path: '/log')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class LogController extends AbstractController
{
    use TableTrait;

    /**
     * Delete the content of the log file (if any).
     */
    #[Route(path: '/delete', name: 'log_delete', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function delete(Request $request, LogService $service, LoggerInterface $logger): Response
    {
        $file = $this->getLogFile($service)?->getFile();
        if (null === $file) {
            return $this->getEmptyResponse();
        }
        $form = $this->createForm(FormType::class);
        if ($this->handleRequestForm($request, $form)) {
            try {
                FileUtils::remove($file);
            } catch (\Exception $e) {
                return $this->renderFormException('log.delete.error', $e, $logger);
            } finally {
                $service->clearCache();
            }

            return $this->redirectToHomePage('log.delete.success');
        }
        $parameters = [
            'form' => $form,
            'file' => $file,
            'size' => FileUtils::formatSize($file),
            'entries' => FileUtils::getLinesCount($file),
            'route' => $this->getDefaultRoute($request),
        ];

        return $this->render('log/log_delete.html.twig', $parameters);
    }

    /**
     * Download the file.
     */
    #[Route(path: '/download', name: 'log_download', methods: Request::METHOD_GET)]
    public function download(LogService $service): Response
    {
        if (!$service->isFileValid()) {
            return $this->getEmptyResponse('log.download.error', FlashType::WARNING);
        }

        return $this->file($service->getFileName());
    }

    /**
     * Export the logs to a Spreadsheet document.
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[Route(path: '/excel', name: 'log_excel', methods: Request::METHOD_GET)]
    public function excel(LogService $service): Response
    {
        $logFile = $this->getLogFile($service);
        if (!$logFile instanceof LogFile) {
            return $this->getEmptyResponse();
        }
        $doc = new LogsDocument($this, $logFile);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export to PDF the content of the log file.
     */
    #[Route(path: '/pdf', name: 'log_pdf', methods: Request::METHOD_GET)]
    public function pdf(LogService $service): Response
    {
        $logFile = $this->getLogFile($service);
        if (!$logFile instanceof LogFile) {
            return $this->getEmptyResponse();
        }
        $doc = new LogsReport($this, $logFile);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Clear the log file cache.
     */
    #[Route(path: '/refresh', name: 'log_refresh', methods: Request::METHOD_GET)]
    public function refresh(Request $request, LogService $service): Response
    {
        $service->clearCache();
        $route = $this->getDefaultRoute($request);

        return $this->redirectToRoute($route);
    }

    /**
     * Show properties of a log entry.
     */
    #[Route(path: '/show/{id}', name: 'log_show', requirements: ['id' => Requirement::DIGITS], methods: Request::METHOD_GET)]
    public function show(Request $request, int $id, LogService $service): Response
    {
        $item = $service->getLog($id);
        if (!$item instanceof Log) {
            $this->warningTrans('log.show.not_found');
            $route = $this->getDefaultRoute($request);

            return $this->redirectToRoute($route);
        }

        return $this->render('log/log_show.html.twig', ['item' => $item]);
    }

    /**
     * Render the table view.
     */
    #[Route(path: '', name: 'log_table', methods: Request::METHOD_GET)]
    public function table(
        LogTable $table,
        LoggerInterface $logger,
        #[MapQueryString]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'log/log_table.html.twig');
    }

    /**
     * Gets the default route name used to display the logs.
     */
    private function getDefaultRoute(Request $request): string
    {
        return $this->getRequestString($request, 'route', 'log_table');
    }

    private function getEmptyResponse(
        string $message = 'log.list.empty',
        FlashType $type = FlashType::INFO
    ): RedirectResponse {
        return $this->redirectToHomePage($message, type: $type);
    }

    private function getLogFile(LogService $service): ?LogFile
    {
        $logFile = $service->getLogFile();
        if (!$logFile instanceof LogFile || $logFile->isEmpty()) {
            return null;
        }

        return $logFile;
    }
}
