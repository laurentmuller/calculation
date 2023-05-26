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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to select the website theme.
 */
#[AsController]
#[Route(path: '/user')]
#[IsGranted(RoleInterface::ROLE_USER)]
class ThemeController extends AbstractController
{
    use CookieTrait;

    #[Route(path: '/theme/dialog', name: 'user_theme_dialog')]
    public function dialog(Request $request, ThemeExtension $extension): JsonResponse
    {
        $theme = $extension->getTheme($request);
        $is_dark = $extension->isDarkTheme($request);
        $result = $this->renderView('dialog/dialog_theme.html.twig', [
            'theme_selection' => $theme,
            'is_dark' => $is_dark,
        ]);

        return $this->json($result);
    }

    #[Route(path: '/theme', name: 'user_theme')]
    public function theme(Request $request, ThemeExtension $extension): Response
    {
        $theme = $extension->getTheme($request);
        $form = $this->createEditForm($theme);
        if ($this->handleRequestForm($request, $form)) {
            /** @psalm-var array{theme: Theme} $data */
            $data = $form->getData();
            $theme = $data['theme'];
            $response = $this->redirectToHomePage($theme->getSuccess(), ['%name%' => $theme]);
            $this->saveThemeCookie($response, $theme);

            return $response;
        }

        return $this->render('user/user_theme_html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @psalm-return FormInterface<array{theme: Theme}>
     */
    private function createEditForm(Theme $theme): FormInterface
    {
        $dark = Theme::DARK === $theme;
        $helper = $this->createFormHelper(data: ['theme' => $theme]);

        /** @psalm-var FormInterface<array{theme: Theme}> $form */
        $form = $helper->field('theme')
            ->updateOption('label', false)
            ->updateOption('expanded', true)
            ->updateOption('choice_attr', fn (Theme $theme): array => [
                'data-help' => $this->trans($theme->getHelp()),
                'data-icon' => $dark ? $theme->getIconLight() : $theme->getIconDark(),
            ])
            ->addEnumType(Theme::class)
            ->createForm();

        return $form;
    }

    private function saveThemeCookie(Response $response, Theme $theme): void
    {
        $this->setCookie(
            response: $response,
            key: ThemeExtension::KEY_THEME,
            value: $theme->value,
            path: $this->getCookiePath(),
            httpOnly: false
        );
    }
}
