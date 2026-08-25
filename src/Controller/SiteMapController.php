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
use App\Attribute\IsUser;
use App\Utils\FileUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Controller to display the site map.
 */
#[IsUser]
class SiteMapController extends AbstractController
{
    /** The logout route name. */
    public const string LOGOUT_ROUTE = 'app_logout';

    private readonly array $content;

    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/data/site_map.json')]
        string $file,
        private readonly RouterInterface $router
    ) {
        $this->content = FileUtils::decodeJson($file);
    }

    #[GetRoute(path: '/sitemap', name: 'site_map')]
    public function index(): Response
    {
        $missingRoutes = $this->getMissingRoutes();
        if ([] !== $missingRoutes) {
            $message = \sprintf(
                'Unable to generate URL for the named route(s): "%s".',
                \implode('", "', $missingRoutes)
            );
            throw new UnprocessableEntityHttpException($message);
        }

        return $this->render('sitemap/sitemap.html.twig', ['content' => $this->content]);
    }

    /**
     * @return string[]
     */
    private function getExistingRoutes(): array
    {
        return \array_keys($this->router->getRouteCollection()->all());
    }

    /**
     * @return string[]
     */
    private function getMissingRoutes(): array
    {
        $existingRoutes = $this->getExistingRoutes();
        $requiredRoutes = $this->getRequiredRoutes($this->content);

        return \array_diff($requiredRoutes, $existingRoutes);
    }

    /**
     * @return string[]
     */
    private function getRequiredRoutes(array $values): array
    {
        $results = [];
        foreach ($values as $key => $value) {
            if ('route' === $key && \is_string($value) && self::LOGOUT_ROUTE !== $value) {
                $results[] = $value;
            } elseif (\is_array($value)) {
                $results = \array_merge($results, $this->getRequiredRoutes($value));
            }
        }

        return \array_unique($results);
    }
}
