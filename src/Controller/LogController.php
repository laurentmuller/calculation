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

use App\Report\LogReport;
use App\Service\LogService;
use App\Spreadsheet\LogsDocument;
use App\Table\LogTable;
use App\Traits\TableTrait;
use App\Util\FileUtils;
use App\Util\Utils;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The log controller.
 */
#[AsController]
#[IsGranted('ROLE_ADMIN')]
#[Route(path: '/log')]
class LogController extends AbstractController
{
    use TableTrait;

    /**
     * Delete the content of the log file (if any).
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \ReflectionException
     */
    #[Route(path: '/delete', name: 'log_delete')]
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
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[Route(path: '/excel', name: 'log_excel')]
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
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Route(path: '/pdf', name: 'log_pdf')]
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
     * @throws \Psr\Cache\InvalidArgumentException
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
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Route(path: '/show/{id}', name: 'log_show', requirements: ['id' => self::DIGITS])]
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
     * @throws \ReflectionException
     */
    #[Route(path: '', name: 'log_table')]
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
