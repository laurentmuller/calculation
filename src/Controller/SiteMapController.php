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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display the site map.
 */
#[AsController]
#[IsGranted(RoleInterface::ROLE_USER)]
class SiteMapController extends AbstractController
{
    #[Route(path: '/sitemap', name: 'site_map')]
    public function invoke(): Response
    {
        return $this->render('sitemap/sitemap.html.twig');
    }
}
