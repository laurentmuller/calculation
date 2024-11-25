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

use App\Attribute\Get;
use App\Interfaces\RoleInterface;
use App\Utils\FileUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\RouterInterface;
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
        string $file,
        private readonly RouterInterface $router
    ) {
        $this->content = FileUtils::decodeJson($file);
    }

    #[Get(path: '/sitemap', name: 'site_map')]
    public function index(): Response
    {
        $missingRoutes = $this->getMissingRoutes();
        if ([] !== $missingRoutes) {
            $message = \sprintf('Unable to generate URL for the named route: "%s".', \implode('", "', $missingRoutes));
            throw $this->createNotFoundException($message);
        }

        return $this->render('sitemap/sitemap.html.twig', ['content' => $this->content]);
    }

    /**
     * @return string[]
     */
    private function getMissingRoutes(): array
    {
        $existing = $this->getRouteNames();
        $routes = $this->loadRoutes($this->content);

        return \array_diff($routes, $existing);
    }

    /**
     * @return string[]
     */
    private function getRouteNames(): array
    {
        return \array_keys($this->router->getRouteCollection()->all());
    }

    /**
     * @return string[]
     */
    private function loadRoutes(array $values): array
    {
        $results = [];
        /** @psalm-var string|array $value */
        foreach ($values as $key => $value) {
            if ('route' === $key && \is_string($value)) {
                $results[] = $value;
            } elseif (\is_array($value)) {
                $results = \array_merge($results, $this->loadRoutes($value));
            }
        }

        return \array_unique($results);
    }
}
