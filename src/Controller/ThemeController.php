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
use App\Traits\CookieTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to select the website theme.
 */
#[AsController]
#[Route(path: '/theme')]
#[IsGranted(RoleInterface::ROLE_USER)]
class ThemeController extends AbstractController
{
    use CookieTrait;

    /**
     * The key name for dark theme cookie.
     */
    final public const KEY_DARK = 'THEME_DARK';

    /**
     * Save the theme preference to cookies.
     */
    #[Route(path: '/save', name: 'theme_save')]
    public function saveTheme(Request $request): JsonResponse
    {
        $dark = $this->getRequestBoolean($request, 'dark');
        $response = $this->jsonTrue([
            'message' => $this->trans($dark ? 'theme.dark_success' : 'theme.light_success'),
        ]);
        $path = $this->getParameterString('cookie_path');
        $this->updateCookie($response, self::KEY_DARK, $dark ? 1 : null, '', $path);

        return $response;
    }
}
