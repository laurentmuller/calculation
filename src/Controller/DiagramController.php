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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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
        $diagram = $this->findDiagram($request->getSession(), $name);
        if (\is_string($diagram)) {
            throw $this->createNotFoundException($diagram);
        }

        return $this->render('test/diagram.html.twig', [
            'diagrams' => $this->getDiagrams(),
            'diagram' => $diagram,
        ]);
    }

    /**
     * Load a diagram file.
     */
    #[GetRoute(path: '/load', name: 'load')]
    public function load(Request $request, #[MapQueryParameter] string $name): JsonResponse
    {
        $diagram = $this->findDiagram($request->getSession(), $name);
        if (\is_string($diagram)) {
            return $this->jsonFalse(['message' => $diagram]);
        }

        return $this->jsonTrue(['diagram' => $diagram]);
    }

    private function findDiagram(SessionInterface $session, ?string $name): array|string
    {
        $name ??= $this->getDiagram($session);
        if (!$this->service->hasDiagram($name)) {
            return $this->trans('diagram.error_not_found', ['%name%' => $name]);
        }
        $diagram = $this->service->getDiagram($name);
        $this->saveDiagram($session, $name);

        return $diagram;
    }

    private function getDiagram(SessionInterface $session): string
    {
        return (string) $session->get(self::KEY_DIAGRAM, self::DEFAULT_DIAGRAM);
    }

    private function getDiagrams(): array
    {
        return \array_column($this->service->getDiagrams(), 'title', 'name');
    }

    private function saveDiagram(SessionInterface $session, string $name): void
    {
        $session->set(self::KEY_DIAGRAM, $name);
    }
}
