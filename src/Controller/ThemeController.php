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
use App\Interfaces\RoleInterface;
use App\Service\ThemeService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for website theme.
 */
#[Route(path: '/theme', name: 'theme_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class ThemeController extends AbstractController
{
    #[GetRoute(path: '/dialog', name: 'dialog')]
    public function dialog(): JsonResponse
    {
        return $this->json(
            $this->renderView('dialog/dialog_theme.html.twig')
        );
    }

    #[GetRoute(path: '/save', name: 'save')]
    public function saveTheme(Request $request, ThemeService $service): JsonResponse
    {
        $default = $service->getTheme($request);
        $theme = $this->getRequestEnum($request, 'theme', $default);

        $response = $this->jsonTrue(
            ['message' => $this->trans($theme->getSuccess())]
        );
        $service->saveTheme($response, $theme);

        return $response;
    }
}
