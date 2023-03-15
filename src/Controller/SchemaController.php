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
     * Constructor.
     */
    public function __construct(private readonly SchemaService $service)
    {
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    #[Route(path: '', name: 'schema')]
    public function index(): Response
    {
        return $this->render('schema/index.html.twig', [
            'tables' => $this->service->getTables(),
        ]);
    }

    /**
     * Export the schema to a PDF document.
     */
    #[Route(path: '/pdf', name: 'schema_pdf')]
    public function pdf(): PdfResponse
    {
        $report = new SchemaReport($this, $this->service);

        return $this->renderPdfDocument($report);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    #[Route(path: '/{name}', name: 'schema_table')]
    public function table(string $name): Response
    {
        $table = $this->service->getTable($name);

        return $this->render('schema/table.html.twig', $table);
    }
}
