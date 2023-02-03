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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
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
    /**
     * Display the help for a dialog.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Route(path: '/dialog/{id}', name: 'help_dialog')]
    public function dialog(string $id, HelpService $service): Response
    {
        /** @psalm-var HelpDialogType|null $dialog */
        $dialog = $service->findDialog($id);
        if (null === $dialog) {
            throw $this->createNotFoundException("Unable to find the resource for the dialog '$id'.");
        }

        /** @psalm-var HelpEntityType|null $entity */
        $entity = $service->findEntityByDialog($dialog);

        return $this->render('help/help_dialog.html.twig', [
            'service' => $service,
            'dialog' => $dialog,
            'entity' => $entity,
        ]);
    }

    /**
     * Display the help for an entity.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Route(path: '/entity/{id}', name: 'help_entity')]
    public function entity(string $id, HelpService $service): Response
    {
        /** @psalm-var HelpEntityType|null $entity */
        $entity = $service->findEntity($id);
        if (null === $entity) {
            throw $this->createNotFoundException("Unable to find the resource for the object '$id'.");
        }

        return $this->render('help/help_entity.html.twig', [
            'service' => $service,
            'entity' => $entity,
        ]);
    }

    /**
     * Display the help index.
     */
    #[Route(path: '', name: 'help')]
    public function index(HelpService $service): Response
    {
        return $this->render('help/help_index.html.twig', [
            'service' => $service,
        ]);
    }

    /**
     * Export the help to a PDF document.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/pdf', name: 'help_pdf')]
    public function pdf(HelpService $service): PdfResponse
    {
        $doc = new HelpReport($this, $service);
        $name = $this->trans('help.title_name', ['%name%' => $this->getApplicationName()]);

        return $this->renderPdfDocument(doc: $doc, name: $name);
    }
}
