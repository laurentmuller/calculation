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
use App\Interfaces\RoleInterface;
use App\Report\SchemaReport;
use App\Response\PdfResponse;
use App\Service\SchemaService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display the database schema.
 */
#[AsController]
#[Route(path: '/schema')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class SchemaController extends AbstractController
{
    /**
     * Display information for tables.
     */
    #[GetRoute(path: '', name: 'schema')]
    public function index(SchemaService $service): Response
    {
        return $this->render('schema/index.html.twig', [
            'tables' => $service->getTables(),
        ]);
    }

    /**
     * Export the schema to a PDF document.
     */
    #[GetRoute(path: '/pdf', name: 'schema_pdf')]
    public function pdf(SchemaService $service): PdfResponse
    {
        $report = new SchemaReport($this, $service);

        return $this->renderPdfDocument($report);
    }

    /**
     * Display information for the given table name.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    #[GetRoute(path: '/{name}', name: 'schema_table')]
    public function table(string $name, SchemaService $service): Response
    {
        try {
            $service->tableExist($name);

            return $this->render('schema/table.html.twig', $service->getTable($name));
        } catch (\Doctrine\DBAL\Exception $e) {
            $msg = $this->trans('schema.table.error', ['%name%' => $name]);
            throw $this->createNotFoundException($msg, $e);
        }
    }
}
