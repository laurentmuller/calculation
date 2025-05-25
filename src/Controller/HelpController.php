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
use App\Attribute\PostRoute;
use App\Interfaces\RoleInterface;
use App\Model\HelpDownloadQuery;
use App\Report\HelpReport;
use App\Response\PdfResponse;
use App\Service\HelpService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display help.
 */
#[AsController]
#[Route(path: '/help', name: 'help_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class HelpController extends AbstractController
{
    public function __construct(private readonly HelpService $service)
    {
    }

    /**
     * Display help for a dialog.
     */
    #[GetRoute(path: '/dialog/{id}', name: 'dialog')]
    public function dialog(string $id): Response
    {
        $dialog = $this->service->findDialog($id);
        if (null === $dialog) {
            throw $this->createTranslatedNotFoundException('help.errors.page_not_found', ['%id%' => $id]);
        }
        $entity = $this->service->findEntity($dialog);

        return $this->render('help/help_dialog.html.twig', [
            'service' => $this->service,
            'dialog' => $dialog,
            'entity' => $entity,
        ]);
    }

    /**
     * Display help for dialogs.
     */
    #[GetRoute(path: '/dialogs', name: 'dialogs')]
    public function dialogs(): Response
    {
        $dialogs = $this->service->getDialogs();
        if ([] === $dialogs) {
            throw $this->createTranslatedNotFoundException('help.errors.dialogs_not_found');
        }

        $this->service->sortByName($dialogs);

        return $this->render('help/help_dialogs.html.twig', [
            'service' => $this->service,
            'dialogs' => $dialogs,
        ]);
    }

    /**
     * Save the screenshot image.
     */
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[PostRoute(path: '/download', name: 'download')]
    public function download(
        #[MapRequestPayload]
        HelpDownloadQuery $query,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
        Filesystem $fs
    ): JsonResponse {
        $targetImage = $this->getTargetImage($query);
        if (null === $targetImage) {
            return $this->jsonFalse(['message' => 'Unable to get target image.']);
        }
        $targetPath = $this->getTargetPath($query);
        if (null === $targetPath) {
            return $this->jsonFalse(['message' => 'Unable to get the target path.']);
        }

        try {
            $fs->dumpFile($projectDir . '/var/cache/help/' . $targetPath, $targetImage);

            return $this->jsonTrue(['message' => \sprintf('Image "%s" saved successfully.', $targetPath)]);
        } catch (IOException $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Display help for entities.
     */
    #[GetRoute(path: '/entities', name: 'entities')]
    public function entities(): Response
    {
        $entities = $this->service->getEntities();
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('help.errors.entities_not_found');
        }

        $this->service->sortByName($entities);

        return $this->render('help/help_entities.html.twig', [
            'entities' => $entities,
        ]);
    }

    /**
     * Display help for an entity.
     */
    #[GetRoute(path: '/entity/{id}', name: 'entity')]
    public function entity(string $id): Response
    {
        $entity = $this->service->findEntity($id);
        if (null === $entity) {
            throw $this->createTranslatedNotFoundException('help.errors.page_not_found', ['%id%' => $id]);
        }

        return $this->render('help/help_entity.html.twig', [
            'service' => $this->service,
            'entity' => $entity,
        ]);
    }

    /**
     * Display help index.
     */
    #[IndexRoute]
    public function index(): Response
    {
        return $this->render('help/help_index.html.twig', ['service' => $this->service]);
    }

    /**
     * Export the help to a PDF document.
     */
    #[PdfRoute]
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
        /** @phpstan-var string $first */
        $first = \reset($parts);
        /** @phpstan-var string $last */
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
