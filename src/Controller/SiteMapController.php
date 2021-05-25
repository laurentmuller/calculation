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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use SlopeIt\BreadcrumbBundle\Annotation\Breadcrumb;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to display the site map.
 *
 * @author Laurent Muller
 */
class SiteMapController extends AbstractController
{
    /**
     * Display the site map.
     *
     * @Route("/sitemap", name="site_map")
     * @IsGranted("ROLE_USER")
     * @Breadcrumb({
     *     {"label" = "index.title", "route" = "homepage"},
     *     {"label" = "index.menu_site_map"}
     * })
     */
    public function invoke(): Response
    {
        return $this->render('home/sitemap.html.twig');
    }
}
