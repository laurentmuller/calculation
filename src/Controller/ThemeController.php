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

use App\Form\User\ThemeType;
use App\Model\Theme;
use App\Service\ThemeService;
use App\Traits\CookieTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

use function Symfony\Component\String\u;

/**
 * Controller to select the website theme.
 */
#[AsController]
#[Route(path: '/user')]
class ThemeController extends AbstractController
{
    use CookieTrait;

    /**
     * Display the page to select the website theme.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \ReflectionException
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/theme', name: 'user_theme')]
    public function invoke(Request $request, ThemeService $service): Response
    {
        $data = [
            'theme' => $service->getCurrentTheme($request),
            'background' => $service->getThemeBackground($request),
        ];

        $form = $this->createForm(ThemeType::class, $data);
        if ($this->handleRequestForm($request, $form)) {
            /** @psalm-var array{theme: Theme, background: string} $data */
            $data = $form->getData();
            $theme = $data['theme'];
            $css = $theme->getCss();
            $dark = $theme->isDark();
            $background = $data['background'];

            // check for default values
            if (ThemeService::DEFAULT_CSS === $css) {
                $css = null;
            }
            if (ThemeService::DEFAULT_BACKGROUND === $background) {
                $background = null;
            }
            if (ThemeService::DEFAULT_DARK === $dark) {
                $dark = null;
            }

            // create response and update cookies
            $response = $this->redirectToHomePage();
            $path = $this->getParameterString('cookie_path');
            $this->updateCookie($response, ThemeService::KEY_CSS, $css, '', $path);
            $this->updateCookie($response, ThemeService::KEY_DARK, $dark, '', $path);
            $this->updateCookie($response, ThemeService::KEY_BACKGROUND, $background, '', $path);

            $this->successTrans('theme.success', ['%name%' => $theme->getName()]);

            return $response;
        }

        // render
        return $this->renderForm('user/user_theme.html.twig', [
            'asset_base' => $this->getAssetBase($request),
            'themes' => $service->getThemes(),
            'theme' => $data['theme'],
            'form' => $form,
        ]);
    }

    /**
     * Gets the asset base.
     */
    private function getAssetBase(Request $request): string
    {
        return u($request->getSchemeAndHttpHost())
            ->append($this->getParameterString('cookie_path'))
            ->ensureEnd('/')
            ->toString();
    }
}
