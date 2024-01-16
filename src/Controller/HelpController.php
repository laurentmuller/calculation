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
use App\Model\HelpDownloadQuery;
use App\Report\HelpReport;
use App\Response\PdfResponse;
use App\Service\HelpService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
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
        $entity = $this->service->findEntityByDialog($dialog);

        return $this->render('help/help_dialog.html.twig', [
            'service' => $this->service,
            'dialog' => $dialog,
            'entity' => $entity,
        ]);
    }

    /**
     * Display help for dialogs.
     */
    #[Route(path: '/dialogs', name: 'help_dialogs', methods: Request::METHOD_GET)]
    public function dialogs(): Response
    {
        $dialogs = $this->service->getDialogs();
        if ([] === $dialogs) {
            throw $this->createNotFoundException('Unable to find dialogs.');
        }

        return $this->render('help/help_dialogs.html.twig', [
            'dialogs' => $dialogs,
        ]);
    }

    /**
     * Save screenshot image.
     */
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[Route(path: '/download', name: 'help_download', methods: Request::METHOD_POST)]
    public function download(
        #[MapRequestPayload]
        HelpDownloadQuery $query,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
        Filesystem $filesystem
    ): JsonResponse {
        $targetImage = $this->getTargetImage($query);
        if (null === $targetImage) {
            return $this->jsonFalse(['message' => 'Unable to get target image.']);
        }
        $targetPath = $this->getTargetPath($query);
        if (null === $targetPath) {
            return $this->jsonFalse(['message' => 'Unable to get the target path.']);
        }
        $filesystem->dumpFile($projectDir . '/var/cache/help/' . $targetPath, $targetImage);

        return $this->jsonTrue([
            'message' => 'Saved image successfully.',
            'target' => $targetPath,
        ]);
    }

    /**
     * Display help for entities.
     */
    #[Route(path: '/entities', name: 'help_entities', methods: Request::METHOD_GET)]
    public function entities(): Response
    {
        $entities = $this->service->getEntities();
        if ([] === $entities) {
            throw $this->createNotFoundException('Unable to find entities.');
        }

        return $this->render('help/help_entities.html.twig', [
            'entities' => $entities,
        ]);
    }

    /**
     * Display help for an entity.
     */
    #[Route(path: '/entity/{id}', name: 'help_entity', methods: Request::METHOD_GET)]
    public function entity(string $id): Response
    {
        $entity = $this->service->findEntity($id);
        if (null === $entity) {
            throw $this->createNotFoundException("Unable to find the resource for the entity '$id'.");
        }

        return $this->render('help/help_entity.html.twig', [
            'service' => $this->service,
            'entity' => $entity,
        ]);
    }

    /**
     * Display help index.
     */
    #[Route(path: '/', name: 'help', methods: Request::METHOD_GET)]
    public function index(): Response
    {
        return $this->render('help/help_index.html.twig', [
            'mainMenu' => $this->service->getMainMenu(),
            'mainMenus' => $this->service->getMainMenus(),
            'entities' => $this->service->getEntities(),
            'dialogs' => $this->service->getDialogs(),
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

    private function getTargetImage(HelpDownloadQuery $query): ?string
    {
        $image = $query->image;
        $parts = \explode(';base64,', $image);
        if (2 !== \count($parts)) {
            return null;
        }
        $decoded = \base64_decode($parts[1], true);
        if (!\is_string($decoded)) {
            return null;
        }

        return $decoded;
    }

    private function getTargetPath(HelpDownloadQuery $query): ?string
    {
        $location = $query->location;
        $parts = \array_filter(\explode('/', $location));
        if ([] === $parts) {
            if ('/' !== $location) {
                return null;
            }
            $parts = ['', 'index'];
        }
        if (\is_numeric(\end($parts))) {
            \array_pop($parts);
        }
        $first = \reset($parts);
        $last = \end($parts);
        if ($first === $last) {
            $last = 'list';
        }
        $name = \ltrim(\sprintf('%s_%s', $first, $last), '_');
        if ($query->index > 0) {
            $name .= \sprintf('_%d', $query->index);
        }

        return \sprintf('%s/%s.png', $first, $name);
    }
}
