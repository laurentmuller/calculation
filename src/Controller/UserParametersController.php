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
use App\Form\User\UserParametersType;
use App\Interfaces\RoleInterface;
use App\Service\ThemeService;
use App\Service\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display user's preferences.
 */
#[AsController]
#[Route(path: '/user')]
#[IsGranted(RoleInterface::ROLE_USER)]
class UserParametersController extends AbstractController
{
    #[Route(path: '/parameters', name: 'user_parameters')]
    public function invoke(Request $request, UserService $userService, ThemeService $themeService): Response
    {
        $properties = $userService->getProperties();
        $properties[UserParametersType::THEME_FIELD] = $themeService->getTheme($request);

        $form = $this->createForm(UserParametersType::class, $properties);
        if ($this->handleRequestForm($request, $form)) {
            /** @psalm-var array<string, mixed> $data */
            $data = $form->getData();
            /** @psalm-var Theme $theme */
            $theme = $data[UserParametersType::THEME_FIELD];

            // save properties
            unset($data[UserParametersType::THEME_FIELD]);
            $userService->setProperties($data);

            // save theme
            $response = $this->redirectToHomePage('user.parameters.success');
            $themeService->saveTheme($response, $theme, $this->getCookiePath());

            return $response;
        }

        return $this->render('user/user_parameters.html.twig', [
            'form' => $form,
        ]);
    }
}
