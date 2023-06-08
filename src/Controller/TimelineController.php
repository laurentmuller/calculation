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
use App\Service\TimelineService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display calculations timeline.
 */
#[AsController]
#[Route(path: '/test/timeline')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class TimelineController extends AbstractController
{
    public function __construct(private readonly TimelineService $service)
    {
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '', name: 'timeline', methods: Request::METHOD_GET)]
    public function current(
        #[MapQueryParameter] string $date = null,
        #[MapQueryParameter] string $interval = null
    ): Response {
        $parameters = $this->service->current($date, $interval);

        return $this->renderTimeline($parameters);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/first', name: 'timeline_first', methods: Request::METHOD_GET)]
    public function first(#[MapQueryParameter] string $interval = null): Response
    {
        $parameters = $this->service->first($interval);

        return $this->renderTimeline($parameters);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/last', name: 'timeline_last', methods: Request::METHOD_GET)]
    public function last(#[MapQueryParameter] string $interval = null): Response
    {
        $parameters = $this->service->last($interval);

        return $this->renderTimeline($parameters);
    }

    private function renderTimeline(array $parameters): Response
    {
        return $this->render('test/timeline.html.twig', $parameters);
    }
}
