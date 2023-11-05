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

use App\Attribute\GetRoute;
use App\Interfaces\RoleInterface;
use App\Utils\FileUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display the site map.
 */
#[AsController]
#[IsGranted(RoleInterface::ROLE_USER)]
class SiteMapController extends AbstractController
{
    private readonly array $content;

    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/data/site_map.json')]
        string $file
    ) {
        $this->content = FileUtils::decodeJson($file);
    }

    #[GetRoute(path: '/sitemap', name: 'site_map')]
    public function invoke(): Response
    {
        return $this->render('sitemap/sitemap.html.twig', ['content' => $this->content]);
    }
}
