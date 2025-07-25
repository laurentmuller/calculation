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

use App\Attribute\GetRoute;
use App\Attribute\IndexRoute;
use App\Attribute\PdfRoute;
use App\Interfaces\RoleInterface;
use App\Report\SchemaReport;
use App\Response\PdfResponse;
use App\Service\FontAwesomeImageService;
use App\Service\SchemaService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display the database schema.
 */
#[Route(path: '/schema', name: 'schema_')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class SchemaController extends AbstractController
{
    /**
     * Display information for tables.
     */
    #[IndexRoute]
    public function index(SchemaService $service): Response
    {
        return $this->render('schema/index.html.twig', [
            'tables' => $service->getTables(),
        ]);
    }

    /**
     * Export the schema to a PDF document.
     */
    #[PdfRoute]
    public function pdf(
        SchemaService $schemaService,
        FontAwesomeImageService $imageService
    ): PdfResponse {
        $report = new SchemaReport($this, $schemaService, $imageService);

        return $this->renderPdfDocument($report);
    }

    /**
     * Display information for the given table name.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    #[GetRoute(path: '/{name}', name: 'table')]
    public function table(string $name, SchemaService $service): Response
    {
        if ($service->tableExists($name)) {
            return $this->render('schema/table.html.twig', $service->getTable($name));
        }

        $this->warningTrans('schema.table.error', ['%name%' => $name]);

        return $this->redirectToRoute('schema_index');
    }
}
