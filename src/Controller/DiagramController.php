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
use App\Model\DiagramQuery;
use App\Service\DiagramService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
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
    private const string DEFAULT_DIAGRAM = 'entity_interface';
    private const string KEY_DIAGRAM = 'diagram.name';
    private const string KEY_ZOOM = 'diagram.zoom';

    public function __construct(private readonly DiagramService $service)
    {
    }

    /**
     * Render the diagram view.
     */
    #[IndexRoute]
    public function index(Request $request, #[MapQueryString] DiagramQuery $query = new DiagramQuery()): Response
    {
        $parameters = $this->findDiagram($request->getSession(), $query);
        if (\is_string($parameters)) {
            throw $this->createNotFoundException($parameters);
        }
        $parameters['diagrams'] = $this->service->getDiagrams();

        return $this->render('test/diagram.html.twig', $parameters);
    }

    /**
     * Load a diagram file.
     */
    #[GetRoute(path: '/load', name: 'load')]
    public function load(Request $request, #[MapQueryString] DiagramQuery $query): JsonResponse
    {
        $data = $this->findDiagram($request->getSession(), $query);
        if (\is_string($data)) {
            return $this->jsonFalse(['message' => $data]);
        }

        return $this->jsonTrue($data);
    }

    private function findDiagram(SessionInterface $session, DiagramQuery $query): array|string
    {
        $query->name ??= $this->getDiagram($session);
        $query->zoom ??= $this->getZoom($session);
        if (!$this->service->hasDiagram($query->name)) {
            return $this->trans('diagram.error_not_found', ['%name%' => $query->name]);
        }
        $diagram = $this->service->getDiagram($query->name);
        $this->saveDiagram($session, $query);

        return [
            'diagram' => $diagram,
            'zoom' => $query->zoom,
        ];
    }

    private function getDiagram(SessionInterface $session): string
    {
        return (string) $session->get(self::KEY_DIAGRAM, self::DEFAULT_DIAGRAM);
    }

    private function getZoom(SessionInterface $session): float
    {
        return (float) $session->get(self::KEY_ZOOM, 1.0);
    }

    private function saveDiagram(SessionInterface $session, DiagramQuery $query): void
    {
        $session->set(self::KEY_DIAGRAM, $query->name);
        $session->set(self::KEY_ZOOM, $query->zoom);
    }
}
