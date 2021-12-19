<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Controller;

use App\Report\HelpReport;
use App\Response\PdfResponse;
use App\Service\HelpService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to display help.
 *
 * @author Laurent Muller
 *
 * @Route("/help")
 * @IsGranted("ROLE_USER")
 */
class HelpController extends AbstractController
{
    /**
     * Display the help for a dialog.
     *
     * @Route("/dialog/{id}", name="help_dialog")
     */
    public function dialog(string $id, HelpService $service): Response
    {
        $dialog = $service->findDialog($id);
        if (null === $dialog) {
            throw new NotFoundHttpException("Unable to find the resource for the dialog '$id'.");
        }

        $entity = isset($dialog['entity']) ? $service->findEntity($dialog['entity']) : null;

        return $this->renderForm('help/help_dialog.html.twig', [
            'service' => $service,
            'dialog' => $dialog,
            'entity' => $entity,
        ]);
    }

    /**
     * Display the help for an entity.
     *
     * @Route("/entity/{id}", name="help_entity")
     */
    public function entity(string $id, HelpService $service): Response
    {
        $entity = $service->findEntity($id);
        if (null === $entity) {
            throw new NotFoundHttpException("Unable to find the resource for the object '$id'.");
        }

        return $this->renderForm('help/help_entity.html.twig', [
            'service' => $service,
            'entity' => $entity,
        ]);
    }

    /**
     * Display the help index.
     *
     * @Route("", name="help")
     */
    public function index(HelpService $service): Response
    {
        return $this->renderForm('help/help_index.html.twig', [
            'service' => $service,
            'help' => $service->getHelp(),
        ]);
    }

    /**
     * Export the help to a PDF document.
     *
     * @Route("/pdf", name="help_pdf")
     */
    public function pdf(HelpService $service): PdfResponse
    {
        $doc = new HelpReport($this, $service);

        return $this->renderPdfDocument($doc);
    }
}
