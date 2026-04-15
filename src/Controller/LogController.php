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
use App\Model\TranslatableFlashMessage;
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
use Symfony\Component\Filesystem\Filesystem;
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

    /** The key to save the display context state. */
    public const string LOG_SHOW_KEY = 'log.context.expended';

    public function __construct(private readonly LogService $service)
    {
    }

    /**
     * Delete the content of the log file (if any).
     */
    #[GetPostRoute(path: '/delete', name: 'delete')]
    public function delete(Request $request, Filesystem $fs, LoggerInterface $logger): Response
    {
        $logFile = $this->getLogFile();
        if (!$logFile instanceof LogFile) {
            return $this->getEmptyResponse();
        }

        $file = $logFile->getFile();
        $form = $this->createForm(FormType::class)
            ->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $fs->remove($file);

                return $this->redirectToHomePage(message: 'log.delete.success');
            } catch (\Exception $e) {
                return $this->renderFormException('log.delete.error', $e, $logger);
            } finally {
                $this->service->clearCache();
            }
        }

        $parameters = [
            'form' => $form,
            'file' => $this->getRelativePath($file),
            'size' => FileUtils::formatSize($file),
            'entries' => FileUtils::getLinesCount($file),
        ];

        return $this->render('log/log_delete.html.twig', $parameters);
    }

    /**
     * Download the file.
     */
    #[GetRoute(path: '/download', name: 'download')]
    public function download(): Response
    {
        $logFile = $this->getLogFile();
        if (!$logFile instanceof LogFile) {
            return $this->getEmptyResponse();
        }

        return $this->file($logFile->getFile());
    }

    /**
     * Export the logs to a Spreadsheet document.
     */
    #[ExcelRoute]
    public function excel(): Response
    {
        $logFile = $this->getLogFile();
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
    public function pdf(FontAwesomeService $service): Response
    {
        $logFile = $this->getLogFile();
        if (!$logFile instanceof LogFile) {
            return $this->getEmptyResponse();
        }

        return $this->renderPdfDocument(new LogsReport($this, $logFile, $service));
    }

    /**
     * Clear the log file cache.
     */
    #[GetRoute(path: '/refresh', name: 'refresh')]
    public function refresh(Request $request): Response
    {
        $this->service->clearCache();

        return $this->redirectToDefaultRoute($request);
    }

    /**
     * Show properties of a log entry.
     */
    #[ShowEntityRoute]
    public function show(Request $request, int $id): Response
    {
        $item = $this->service->getLog($id);
        if (!$item instanceof Log) {
            return $this->redirectToDefaultRoute($request, 'log.show.not_found');
        }
        $expanded = $request->getSession()->get(self::LOG_SHOW_KEY, false);

        return $this->render('log/log_show.html.twig', [
            'item' => $item,
            'expanded' => $expanded,
        ]);
    }

    private function getEmptyResponse(): RedirectResponse
    {
        return $this->redirectToHomePage(
            message: TranslatableFlashMessage::instance(
                message: 'log.list.empty',
                type: FlashType::INFO,
            )
        );
    }

    private function getLogFile(): ?LogFile
    {
        $logFile = $this->service->getLogFile();
        if (!$logFile instanceof LogFile || $logFile->isEmpty()) {
            return null;
        }

        return $logFile;
    }

    private function redirectToDefaultRoute(Request $request, ?string $warning = null): RedirectResponse
    {
        if (null !== $warning) {
            $this->warningTrans($warning);
        }

        return $this->getUrlGenerator()
            ->redirect(request: $request, routeName: 'log_index');
    }
}
