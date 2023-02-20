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
    /**
     * @throws \Exception
     */
    #[Route(path: '', name: 'timeline')]
    public function current(Request $request, TimelineService $service): Response
    {
        $date = $this->getRequestString($request, 'date');
        $interval = $this->getRequestString($request, 'interval');
        $parameters = $service->current($date, $interval);

        return $this->render('test/timeline.html.twig', $parameters);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/first', name: 'timeline_first')]
    public function first(Request $request, TimelineService $service): Response
    {
        $interval = $this->getRequestString($request, 'interval');
        $parameters = $service->first($interval);

        return $this->render('test/timeline.html.twig', $parameters);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: '/last', name: 'timeline_last')]
    public function last(Request $request, TimelineService $service): Response
    {
        $interval = $this->getRequestString($request, 'interval');
        $parameters = $service->last($interval);

        return $this->render('test/timeline.html.twig', $parameters);
    }
}
