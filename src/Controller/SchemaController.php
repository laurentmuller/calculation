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

use App\Attribute\Get;
use App\Interfaces\RoleInterface;
use App\Report\SchemaReport;
use App\Response\PdfResponse;
use App\Service\SchemaService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display the database schema.
 */
#[AsController]
#[Route(path: '/schema', name: 'schema_')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class SchemaController extends AbstractController
{
    /**
     * Display information for tables.
     *
     * @throws InvalidArgumentException
     */
    #[Get(path: '', name: 'index')]
    public function index(SchemaService $service): Response
    {
        return $this->render('schema/index.html.twig', [
            'tables' => $service->getTables(),
        ]);
    }

    /**
     * Export the schema to a PDF document.
     */
    #[Get(path: '/pdf', name: 'pdf')]
    public function pdf(SchemaService $service): PdfResponse
    {
        $report = new SchemaReport($this, $service);

        return $this->renderPdfDocument($report);
    }

    /**
     * Display information for the given table name.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws InvalidArgumentException
     */
    #[Get(path: '/{name}', name: 'table')]
    public function table(string $name, SchemaService $service): Response
    {
        try {
            if ($service->tableExists($name)) {
                return $this->render('schema/table.html.twig', $service->getTable($name));
            }

            $this->warningTrans('schema.table.error', ['%name%' => $name]);

            return $this->redirectToRoute('schema_index');
        } catch (\Doctrine\DBAL\Exception $e) {
            throw $this->createTranslateNotFoundException('schema.table.error', ['%name%' => $name], $e);
        }
    }
}
