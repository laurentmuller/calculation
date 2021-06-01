<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Controller;

use App\Form\User\ThemeType;
use App\Service\ThemeService;
use App\Util\Utils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use SlopeIt\BreadcrumbBundle\Annotation\Breadcrumb;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to select the web site theme.
 *
 * @author Laurent Muller
 *
 * @Route("/user")
 */
class ThemeController extends AbstractController
{
    /**
     * Display the page to select the web site theme.
     *
     * @Route("/theme", name="user_theme")
     * @IsGranted("ROLE_USER")
     * @Breadcrumb({
     *     {"label" = "index.title", "route" = "homepage"},
     *     {"label" = "theme.title"}
     * })
     */
    public function invoke(Request $request, ThemeService $service): Response
    {
        // create form and handle request
        $theme = $service->getCurrentTheme();
        $background = $service->getThemeBackground($request);

        $data = [
            'theme' => $theme,
            'background' => $background,
        ];

        $form = $this->createForm(ThemeType::class, $data);
        if ($this->handleRequestForm($request, $form)) {
            // get values
            $data = $form->getData();
            $theme = $data['theme'];
            $background = $data['background'];
            $dark = $theme->isDark();

            // check values
            $css = $theme->getCss();
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
            $this->updateCookie($response, ThemeService::KEY_CSS, $css)
                ->updateCookie($response, ThemeService::KEY_BACKGROUND, $background)
                ->updateCookie($response, ThemeService::KEY_DARK, (string) $dark);

            $this->succesTrans('theme.success', ['%name%' => $theme->getName()]);

            return $response;
        }

        // render
        return $this->render('user/user_theme.html.twig', [
            'asset_base' => $this->getStringParameter('asset_base'),
            'form' => $form->createView(),
            'themes' => $service->getThemes(),
            'theme' => $theme,
        ]);
    }

    /**
     * Update a response by adding or removing a cookie.
     *
     * @param Response    $response the response to update
     * @param string      $name     the cookie name
     * @param string|null $value    the cookie value or null to remove
     * @param int         $days     the number of days the cookie expires after
     */
    private function updateCookie(Response $response, string $name, ?string $value, int $days = 30): self
    {
        $headers = $response->headers;
        $path = $this->getStringParameter('cookie_path');
        if (Utils::isString($value)) {
            $time = (int) \strtotime("now + {$days} day");
            $secure = (bool) $this->getParameter('cookie_secure');
            $cookie = new Cookie($name, $value, $time, $path, null, $secure, true, true);
            $headers->setCookie($cookie);
        } else {
            $headers->clearCookie($name, $path);
        }

        return $this;
    }
}
