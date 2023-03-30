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

use App\Enums\FlashType;
use App\Interfaces\RoleInterface;
use App\Model\LogFile;
use App\Report\LogReport;
use App\Service\LogService;
use App\Spreadsheet\LogsDocument;
use App\Table\LogTable;
use App\Traits\TableTrait;
use App\Utils\FileUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
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
    #[Route(path: '/delete', name: 'log_delete')]
    public function delete(Request $request, LogService $service, LoggerInterface $logger): Response
    {
        $logFile = $service->getLogFile();
        if (!$logFile instanceof LogFile || $logFile->isEmpty()) {
            return $this->getEmptyResponse();
        }
        $file = $logFile->getFile();
        $form = $this->createForm();
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
            'route' => $this->getRequestString($request, 'route'),
        ];

        return $this->render('log/log_delete.html.twig', $parameters);
    }

    /**
     * Download the file.
     */
    #[Route(path: '/download', name: 'log_download')]
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
    #[Route(path: '/excel', name: 'log_excel')]
    public function excel(LogService $service): Response
    {
        $logFile = $service->getLogFile();
        if (!$logFile instanceof LogFile || $logFile->isEmpty()) {
            return $this->getEmptyResponse();
        }
        $doc = new LogsDocument($this, $logFile);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export to PDF the content of the log file.
     */
    #[Route(path: '/pdf', name: 'log_pdf')]
    public function pdf(LogService $service): Response
    {
        $logFile = $service->getLogFile();
        if (!$logFile instanceof LogFile || $logFile->isEmpty()) {
            return $this->getEmptyResponse();
        }
        $doc = new LogReport($this, $logFile);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Clear the log file cache.
     */
    #[Route(path: '/refresh', name: 'log_refresh')]
    public function refresh(Request $request, LogService $service): Response
    {
        $service->clearCache();
        $route = $this->getDefaultRoute($request);

        return $this->redirectToRoute($route);
    }

    /**
     * Show properties of a log entry.
     */
    #[Route(path: '/show/{id}', name: 'log_show', requirements: ['id' => Requirement::DIGITS])]
    public function show(Request $request, int $id, LogService $service): Response
    {
        if (null === $item = $service->getLog($id)) {
            $this->warningTrans('log.show.not_found');
            $route = $this->getDefaultRoute($request);

            return $this->redirectToRoute($route);
        }

        return $this->render('log/log_show.html.twig', ['item' => $item]);
    }

    /**
     * Render the table view.
     */
    #[Route(path: '', name: 'log_table')]
    public function table(Request $request, LogTable $table, LoggerInterface $logger): Response
    {
        return $this->handleTableRequest($request, $table, 'log/log_table.html.twig', $logger);
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

    private function getEmptyResponse(string $message = 'log.list.empty', FlashType $type = FlashType::INFO): RedirectResponse
    {
        return $this->redirectToHomePage($message, [], $type);
    }
}
