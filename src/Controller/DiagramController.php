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
#[Route(path: '/diagram', name: 'diagram_')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class DiagramController extends AbstractController
{
    private const DEFAULT_DIAGRAM = 'entity_interface';

    public function __construct(private readonly DiagramService $service)
    {
    }

    /**
     * Display a diagram.
     *
     * @throws InvalidArgumentException
     */
    #[Get(path: '', name: 'index')]
    public function index(#[MapQueryParameter] string $name = self::DEFAULT_DIAGRAM): Response
    {
        $file = $this->getFile($name);
        if (null === $file) {
            throw $this->createTranslateNotFoundException('diagram.error_not_found', ['%name%' => $name]);
        }

        return $this->render('test/diagram.html.twig', [
            'file' => $file,
            'files' => $this->getFiles(),
        ]);
    }

    /**
     * Load a diagram.
     *
     * @throws InvalidArgumentException
     */
    #[Get(path: '/load', name: 'load')]
    public function load(#[MapQueryParameter] string $name = self::DEFAULT_DIAGRAM): JsonResponse
    {
        $file = $this->getFile($name);
        if (null === $file) {
            return $this->jsonFalse(['message' => $this->trans('diagram.error_not_found', ['%name%' => $name])]);
        }

        return $this->jsonTrue(['file' => $file]);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getFile(string $name): ?array
    {
        return $this->service->getFile($name);
    }

    /**
     * @return array<string, string>
     *
     * @throws InvalidArgumentException
     */
    private function getFiles(): array
    {
        return \array_column($this->service->getFiles(), 'title', 'name');
    }
}
