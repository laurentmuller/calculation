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

use App\Attribute\ForSuperAdmin;
use App\Attribute\GetRoute;
use App\Attribute\IndexRoute;
use App\Service\DiagramService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to display Mermaid diagrams.
 *
 * @see https://mermaid.js.org/
 */
#[ForSuperAdmin]
#[Route(path: '/diagram', name: 'diagram_')]
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
    public function index(Request $request, #[MapQueryParameter] ?string $name = null): Response
    {
        $file = $this->findFile($request, $name);
        if (\is_string($file)) {
            throw $this->createNotFoundException($file);
        }

        return $this->render('test/diagram.html.twig', [
            'file' => $file,
            'files' => $this->getFiles(),
        ]);
    }

    /**
     * Load a diagram file.
     */
    #[GetRoute(path: '/load', name: 'load')]
    public function load(Request $request, #[MapQueryParameter] string $name): JsonResponse
    {
        $file = $this->findFile($request, $name);
        if (\is_string($file)) {
            return $this->jsonFalse(['message' => $file]);
        }

        return $this->jsonTrue(['file' => $file]);
    }

    private function findFile(Request $request, ?string $name): array|string
    {
        $name ??= $this->getDiagramSession($request);
        if (!$this->service->hasDiagram($name)) {
            return $this->trans('diagram.error_not_found', ['%name%' => $name]);
        }
        $file = $this->service->getDiagram($name);
        $this->setDiagramSession($request, $name);

        return $file;
    }

    private function getDiagramSession(Request $request): string
    {
        return (string) $request->getSession()->get(self::KEY_DIAGRAM, self::DEFAULT_DIAGRAM);
    }

    private function getFiles(): array
    {
        return \array_column($this->service->getDiagrams(), 'title', 'name');
    }

    private function setDiagramSession(Request $request, string $name): void
    {
        $request->getSession()->set(self::KEY_DIAGRAM, $name);
    }
}
