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
use App\Traits\CookieTrait;
use App\Twig\ThemeExtension;
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
    use CookieTrait;

    #[Route(path: '/dialog', name: 'theme_dialog', methods: Request::METHOD_GET)]
    public function dialog(Request $request, ThemeExtension $extension): JsonResponse
    {
        $result = $this->renderView('dialog/dialog_theme.html.twig', [
            'theme_selection' => $extension->getTheme($request),
            'is_dark' => $extension->isDarkTheme($request),
        ]);

        return $this->json($result);
    }

    #[Route(path: '/save', name: 'theme_save', methods: Request::METHOD_GET)]
    public function saveTheme(Request $request, ThemeExtension $extension): JsonResponse
    {
        $theme = $extension->getTheme($request);
        $value = $request->query->getString('theme', $theme->value);
        $theme = Theme::tryFrom($value) ?? $theme;
        $response = $this->jsonTrue(
            ['message' => $this->trans($theme->getSuccess())]
        );
        $this->setCookie($response, ThemeExtension::KEY_THEME, $theme->value);

        return $response;
    }
}
