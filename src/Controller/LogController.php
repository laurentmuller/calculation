<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Controller;

use App\DataTables\LogDataTable;
use App\DataTables\LogFileDataTable;
use App\Entity\Log;
use App\Pdf\PdfResponse;
use App\Report\LogReport;
use App\Repository\LogRepository;
use App\Utils\LogUtils;
use App\Utils\Utils;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The log controler.
 *
 * @author Laurent Muller
 *
 * @Route("/log")
 * @IsGranted("ROLE_ADMIN")
 */
class LogController extends BaseController
{
    /**
     * The log file name.
     */
    private string $logFile;

    /**
     * Constructor.
     */
    public function __construct(KernelInterface $kernel)
    {
        $dir = $kernel->getLogDir();
        $env = $kernel->getEnvironment();
        $sep = \DIRECTORY_SEPARATOR;
        $file = $dir . $sep . $env . '.log';
        if ('/' === $sep) {
            $this->logFile = \str_replace('\\', \DIRECTORY_SEPARATOR, $file);
        } else {
            $this->logFile = \str_replace('/', \DIRECTORY_SEPARATOR, $file);
        }
    }

    /**
     * Logs a Content Security Policy report.
     *
     * @IsGranted("ROLE_USER")
     * @Route("/csp", name="log_csp")
     */
    public function cspViolation(Request $request, LoggerInterface $logger): Response
    {
        $data = \file_get_contents('php://input');
        if ($data = \json_decode($data, true)) {
            $context = \array_filter($data['csp-report'], function ($value) {
                return Utils::isString($value);
            });
            $title = 'CSP Violation';
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
    public function delete(Request $request, LoggerInterface $logger): Response
    {
        // check file
        if (!LogUtils::isFileValid($this->logFile)) {
            $this->infoTrans('logs.show.empty');

            return  $this->redirectToHomePage();
        }

        // handle request
        $form = $this->createFormBuilder()->getForm();
        if ($this->handleRequestForm($request, $form)) {
            try {
                // empty file
                \file_put_contents($this->logFile, '');
            } catch (\Exception $e) {
                $message = $this->trans('logs.delete.error');
                $logger->error($message, ['file' => $this->logFile]);

                return $this->render('@Twig/Exception/exception.html.twig', [
                        'message' => $message,
                        'exception' => $e,
                    ]);
            }

            // OK
            $this->succesTrans('logs.delete.success');

            return  $this->redirectToHomePage();
        }

        // display
        return $this->render('log/log_delete.html.twig', [
            'form' => $form->createView(),
            'file' => $this->logFile,
        ]);
    }

    /**
     * Display the content of the log file as table.
     *
     * @Route("/file", name="log_file", methods={"GET", "POST"})
     */
    public function logFile(Request $request, LogFileDataTable $table): Response
    {
        $table->setFileName($this->logFile);
        $results = $table->handleRequest($request);
        if ($table->isCallback()) {
            return $this->json($results);
        }

        // parameters
        $parameters = [
            'results' => $results,
            'columns' => $table->getColumns(),
            'channels' => $table->getChannels(),
            'levels' => $table->getLevels(),
        ];

        return $this->render('log/log_file.html.twig', $parameters);
    }

    /**
     * Clear the log file cache.
     *
     * @Route("/file/refresh", name="log_file_refresh", methods={"GET", "POST"})
     */
    public function logFileRefresh(LogFileDataTable $table): Response
    {
        $table->clearCachedValues();

        return $this->redirectToRoute('log_file');
    }

    /**
     * Show properties of a log entry.
     *
     * @Route("/file/show/{id}", name="log_file_show", requirements={"id": "\d+" }, methods={"GET"})
     */
    public function logFileShow(int $id, LogFileDataTable $table): Response
    {
        if (null === $item = $table->getLog($id)) {
            $this->warningTrans('logs.show.not_found');

            return $this->redirectToRoute('log_file');
        }

        $parameters = [
            'item' => $item,
            'route' => 'log_file',
        ];

        return $this->render('log/log_show.html.twig', $parameters);
    }

    /**
     * Export to PDF the content of the log file.
     *
     * @Route("/pdf", name="log_pdf")
     */
    public function pdf(Request $request): PdfResponse
    {
        // filters
        $channelFilters = [];
        if ($filter = $request->get('channels')) {
            $channelFilters = \explode('|', $filter);
        }
        $levelFilters = [];
        if ($filter = $request->get('levels')) {
            $levelFilters = \explode('|', $filter);
        }
        $maxLines = (int) $request->get('limit', 50);

        // read entries
        $entries = $this->getLogEntries($maxLines, $channelFilters, $levelFilters);

        // render report
        $report = new LogReport($this);
        $report->setValues($entries);

        return $this->renderDocument($report);
    }

    /**
     * Display the content of the log file.
     *
     * @Route("/show", name="log_show")
     */
    public function show(Request $request): Response
    {
        $maxLines = (int) $request->get('limit', 50);
        $entries = $this->getLogEntries($maxLines);

        return $this->render('log/log_list.html.twig', $entries);
    }

    /**
     * Show properties of a log entry.
     *
     * @Route("/show/{id}", name="log_show_entry", requirements={"id": "\d+" }, methods={"GET"})
     */
    public function showEntity(Request $request, Log $item): Response
    {
        return $this->render('log/log_show.html.twig', ['item' => $item]);
    }

    /**
     * Display the content of the log file as table.
     *
     * @Route("/table", name="log_table", methods={"GET", "POST"})
     */
    public function table(Request $request, LogDataTable $table, LogRepository $repository): Response
    {
        $results = $table->handleRequest($request);
        if ($table->isCallback()) {
            return $this->json($results);
        }
        // attributes
        $attributes = [
            'edit-action' => \json_encode(false),
        ];

        // parameters
        $parameters = [
            'results' => $results,
            'attributes' => $attributes,
            'columns' => $table->getColumns(),
            'levels' => $repository->getLevels(),
            'channels' => $repository->getChannels(),
        ];

        return $this->render('log/log_table.html.twig', $parameters);
    }

    /**
     * Gets the log entries.
     *
     * @param int   $maxLines       the number of lines to returns
     * @param array $channelFilters the channels to skip
     * @param array $levelFilters   the levels to skip
     */
    private function getLogEntries(int $maxLines = 50, array $channelFilters = [], array $levelFilters = []): array
    {
        // load file
        $values = LogUtils::readLog($this->logFile, $maxLines, $channelFilters, $levelFilters);
        if (false === $values) {
            $values = [
                'lines' => false,
                'limit' => 0,
            ];
        }
        $values['file'] = $this->logFile;

        return $values;
    }
}
