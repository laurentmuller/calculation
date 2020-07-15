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
use App\Report\LogReport;
use App\Service\LogService;
use App\Utils\SymfonyUtils;
use App\Utils\Utils;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The log controler.
 *
 * @author Laurent Muller
 *
 * @Route("/log")
 * @IsGranted("ROLE_ADMIN")
 */
class LogController extends AbstractController
{
    /**
     * Display the content of the log file as card.
     *
     * @Route("", name="log_list")
     */
    public function card(LogService $service): Response
    {
        if (!$entries = $service->getEntries()) {
            $this->infoTrans('log.show.empty');

            return $this->redirectToHomePage();
        }

        return $this->render('log/log_card.html.twig', $entries);
    }

    /**
     * Logs a Content Security Policy report.
     *
     * @IsGranted("ROLE_USER")
     * @Route("/csp", name="log_csp")
     */
    public function cspViolation(LoggerInterface $logger): Response
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
    public function delete(Request $request, LogService $service, LoggerInterface $logger): Response
    {
        // get entries
        if (!$service->getEntries()) {
            $this->infoTrans('log.show.empty');

            return  $this->redirectToHomePage();
        }

        // handle request
        $file = $service->getFileName();
        $form = $this->getForm();
        if ($this->handleRequestForm($request, $form)) {
            try {
                // empty file
                \file_put_contents($file, '');
            } catch (\Exception $e) {
                $message = $this->trans('log.delete.error');
                $logger->error($message, ['file' => $file]);

                return $this->render('@Twig/Exception/exception.html.twig', [
                        'message' => $message,
                        'exception' => $e,
                    ]);
            } finally {
                $service->clearCache();
            }

            // OK
            $this->succesTrans('log.delete.success');

            return  $this->redirectToHomePage();
        }

        $parameters = [
            'route' => $request->get('route'),
            'form' => $form->createView(),
            'file' => $file,
            'size' => SymfonyUtils::formatFileSize($file),
            'entries' => SymfonyUtils::getLines($file),
        ];

        // display
        return $this->render('log/log_delete.html.twig', $parameters);
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
            $this->infoTrans('log.show.empty');

            return $this->redirectToHomePage();
        }

        // render report
        $report = new LogReport($this);
        $report->setValues($entries);

        return $this->renderDocument($report);
    }

    /**
     * Clear the log file cache.
     *
     * @Route("/refresh", name="log_refresh", methods={"GET", "POST"})
     */
    public function refresh(Request $request, LogService $service): Response
    {
        $service->clearCache();
        $route = $request->get('route', 'log_table');

        return $this->redirectToRoute($route);
    }

    /**
     * Show properties of a log entry.
     *
     * @Route("/show/{id}", name="log_show", requirements={"id": "\d+" }, methods={"GET"})
     */
    public function show(int $id, LogService $service): Response
    {
        if (null === $item = $service->getLog($id)) {
            $this->warningTrans('log.show.not_found');

            return $this->redirectToRoute('log_table');
        }

        return $this->render('log/log_show.html.twig', ['item' => $item]);
    }

    /**
     * Display the content of the log file as table.
     *
     * @Route("/table", name="log_table", methods={"GET", "POST"})
     */
    public function table(Request $request, LogDataTable $table): Response
    {
        $service = $table->getService();
        if (!$service->getEntries()) {
            $this->infoTrans('log.show.empty');

            return $this->redirectToHomePage();
        }

        $results = $table->handleRequest($request);
        if ($table->isCallback()) {
            return $this->json($results);
        }

        // parameters
        $parameters = [
            'results' => $results,
            'file' => $table->getFileName(),
            'columns' => $table->getColumns(),
            'channels' => $table->getChannels(),
            'levels' => $table->getLevels(),
        ];

        return $this->render('log/log_table.html.twig', $parameters);
    }
}
