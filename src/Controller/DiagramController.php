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
use App\Interfaces\RoleInterface;
use App\Service\DiagramService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display Mermaid diagrams.
 *
 * @see https://mermaid.js.org/
 */
#[AsController]
#[Route(path: '/diagram', name: 'diagram_')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class DiagramController extends AbstractController
{
    private const DEFAULT_DIAGRAM = 'entity_interface';
    private const KEY_DIAGRAM = 'last_diagram';

    public function __construct(private readonly DiagramService $service)
    {
    }

    /**
     * Index of the diagram view.
     */
    #[IndexRoute]
    public function index(Request $request): Response
    {
        $file = $this->findFile($request);
        if (\is_string($file)) {
            throw $this->createNotFoundException($file);
        }

        return $this->render('test/diagram.html.twig', [
            'file' => $file,
            'files' => $this->getFiles(),
        ]);
    }

    /**
     * Load a diagram.
     */
    #[GetRoute(path: '/load', name: 'load')]
    public function load(Request $request): JsonResponse
    {
        $file = $this->findFile($request);
        if (\is_string($file)) {
            return $this->jsonFalse(['message' => $file]);
        }

        return $this->jsonTrue(['file' => $file]);
    }

    private function findFile(Request $request): array|string
    {
        $name = $this->getQueryName($request);
        $file = $this->service->getFile($name);
        if (null === $file) {
            return $this->trans('diagram.error_not_found', ['%name%' => $name]);
        }
        $this->setDiagramSession($request, $name);

        return $file;
    }

    private function getDiagramSession(Request $request): string
    {
        return (string) $request->getSession()->get(self::KEY_DIAGRAM, self::DEFAULT_DIAGRAM);
    }

    private function getFiles(): array
    {
        return \array_column($this->service->getFiles(), 'title', 'name');
    }

    private function getQueryName(Request $request): string
    {
        return $request->query->getString('name', $this->getDiagramSession($request));
    }

    private function setDiagramSession(Request $request, string $name): void
    {
        $request->getSession()->set(self::KEY_DIAGRAM, $name);
    }
}
