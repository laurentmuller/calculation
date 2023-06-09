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

use App\Interfaces\RoleInterface;
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
    #[Route(path: '/dialog', name: 'theme_dialog', methods: Request::METHOD_GET)]
    public function dialog(Request $request, ThemeExtension $extension): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createAccessDeniedException($this->trans('theme.error'));
        }
        $result = $this->renderView('dialog/dialog_theme.html.twig', [
            'theme_selection' => $extension->getTheme($request),
            'is_dark' => $extension->isDarkTheme($request),
        ]);

        return $this->json($result);
    }
}
