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

use App\Enums\Theme;
use App\Interfaces\RoleInterface;
use App\Service\ThemeService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for website theme.
 */
#[AsController]
#[Route(path: '/theme')]
#[IsGranted(RoleInterface::ROLE_USER)]
class ThemeController extends AbstractController
{
    #[Route(path: '/dialog', name: 'theme_dialog', methods: Request::METHOD_GET)]
    public function dialog(Request $request, ThemeService $service): JsonResponse
    {
        $result = $this->renderView('dialog/dialog_theme.html.twig', [
            'theme_selection' => $service->getTheme($request),
            'is_dark' => $service->isDarkTheme($request),
        ]);

        return $this->json($result);
    }

    #[Route(path: '/save', name: 'theme_save', methods: Request::METHOD_GET)]
    public function saveTheme(Request $request, ThemeService $service): JsonResponse
    {
        /** @psalm-var Theme $theme */
        $theme = $request->query->getEnum('theme', Theme::class, $service->getTheme($request));
        $response = $this->jsonTrue(
            ['message' => $this->trans($theme->getSuccess())]
        );
        $service->saveTheme($response, $theme, $this->getCookiePath());

        return $response;
    }
}
