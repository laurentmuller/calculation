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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to load sidebar and navigation bar.
 */
#[AsController]
#[Route(path: '/navigation', name: 'navigation_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class NavigationController extends AbstractController
{
    /**
     * Render the horizontal navigation bar (menu bar).
     */
    #[Get(path: '/horizontal', name: 'horizontal')]
    public function horizontal(): JsonResponse
    {
        return $this->renderNavigation('navigation/horizontal/navigation.html.twig');
    }

    /**
     * Render the vertical navigation bar (sidebar).
     */
    #[Get(path: '/vertical', name: 'vertical')]
    public function vertical(): JsonResponse
    {
        return $this->renderNavigation('navigation/vertical/navigation.html.twig');
    }

    private function renderNavigation(string $template): JsonResponse
    {
        $view = $this->renderView($template);

        return $this->json($view);
    }
}
