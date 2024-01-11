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
use App\Report\HelpReport;
use App\Response\PdfResponse;
use App\Service\HelpService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display help.
 *
 * @psalm-import-type HelpDialogType from HelpService
 * @psalm-import-type HelpEntityType from HelpService
 */
#[AsController]
#[Route(path: '/help')]
#[IsGranted(RoleInterface::ROLE_USER)]
class HelpController extends AbstractController
{
    public function __construct(private readonly HelpService $service)
    {
    }

    /**
     * Display help for a dialog.
     */
    #[Route(path: '/dialog/{id}', name: 'help_dialog', methods: Request::METHOD_GET)]
    public function dialog(string $id): Response
    {
        /** @psalm-var HelpDialogType|null $dialog */
        $dialog = $this->service->findDialog($id);
        if (null === $dialog) {
            throw $this->createNotFoundException("Unable to find the resource for the dialog '$id'.");
        }
        /** @psalm-var HelpEntityType|null $entity */
        $entity = $this->service->findEntityByDialog($dialog);

        return $this->render('help/help_dialog.html.twig', [
            'service' => $this->service,
            'dialog' => $dialog,
            'entity' => $entity,
        ]);
    }

    /**
     * Display help for an entity.
     */
    #[Route(path: '/entity/{id}', name: 'help_entity', methods: Request::METHOD_GET)]
    public function entity(string $id): Response
    {
        /** @psalm-var HelpEntityType|null $entity */
        $entity = $this->service->findEntity($id);
        if (null === $entity) {
            throw $this->createNotFoundException("Unable to find the resource for the object '$id'.");
        }

        return $this->render('help/help_entity.html.twig', [
            'service' => $this->service,
            'entity' => $entity,
        ]);
    }

    /**
     * Display help index.
     */
    #[Route(path: '', name: 'help', methods: Request::METHOD_GET)]
    public function index(): Response
    {
        return $this->render('help/help_index.html.twig', [
            'service' => $this->service,
        ]);
    }

    /**
     * Export the help to a PDF document.
     */
    #[Route(path: '/pdf', name: 'help_pdf', methods: Request::METHOD_GET)]
    public function pdf(): PdfResponse
    {
        $doc = new HelpReport($this, $this->service);
        $name = $this->trans('help.title_name', ['%name%' => $this->getApplicationName()]);

        return $this->renderPdfDocument(doc: $doc, name: $name);
    }
}
