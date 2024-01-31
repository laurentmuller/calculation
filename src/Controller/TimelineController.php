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
use App\Service\TimelineService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
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
    #[Get(path: '', name: 'timeline')]
    public function current(
        #[MapQueryParameter]
        string $date = null,
        #[MapQueryParameter]
        string $interval = null
    ): Response {
        $parameters = $this->service->current($date, $interval);

        return $this->renderTimeline($parameters);
    }

    /**
     * @throws \Exception
     */
    #[Get(path: '/first', name: 'timeline_first')]
    public function first(#[MapQueryParameter] string $interval = null): Response
    {
        $parameters = $this->service->first($interval);

        return $this->renderTimeline($parameters);
    }

    /**
     * @throws \Exception
     */
    #[Get(path: '/last', name: 'timeline_last')]
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
