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

use App\Attribute\Get;
use App\Interfaces\RoleInterface;
use App\Service\DiagramService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display Mermaid diagrams.
 *
 * @see https://mermaid.js.org/
 */
#[AsController]
#[Route(path: '/test')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class DiagramController extends AbstractController
{
    /**
     * Display a diagram.
     *
     * @throws InvalidArgumentException
     */
    #[Get(path: '/diagram', name: 'test_diagram')]
    public function diagram(
        DiagramService $service,
        #[MapQueryParameter]
        string $name = 'relations'
    ): Response {
        $file = $service->getFile($name);
        if (null === $file) {
            throw $this->createNotFoundException("Unable to find the diagram '$name'.");
        }

        return $this->render('test/diagram.html.twig', [
            'file' => $file,
            'files' => $service->getFiles(),
        ]);
    }

    /**
     * Load a diagram.
     *
     * @throws InvalidArgumentException
     */
    #[Get(path: '/diagram/load', name: 'test_diagram_load')]
    public function load(
        DiagramService $service,
        #[MapQueryParameter]
        string $name = 'overall_diagram'
    ): JsonResponse {
        $file = $service->getFile($name);
        if (null === $file) {
            return $this->jsonFalse([
                'message' => "Unable to find the diagram '$name'.",
            ]);
        }

        return $this->jsonTrue(['file' => $file]);
    }
}
