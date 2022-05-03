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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to display the site map.
 */
#[AsController]
class SiteMapController extends AbstractController
{
    /**
     * Display the site map.
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/sitemap', name: 'site_map')]
    public function invoke(): Response
    {
        return $this->renderForm('sitemap/sitemap.html.twig');
    }
}
