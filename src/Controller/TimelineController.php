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
#[Route(path: '/timeline', name: 'timeline')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class TimelineController extends AbstractController
{
    private const KEY_DATE = 'timeline_date';
    private const KEY_INTERVAL = 'timeline_interval';

    /**
     * @throws \Exception
     */
    #[Get(path: '', name: '')]
    public function current(
        TimelineService $service,
        #[MapQueryParameter]
        ?string $date = null,
        #[MapQueryParameter]
        ?string $interval = null
    ): Response {
        $date ??= $this->getSessionString(self::KEY_DATE);
        $interval ??= $this->getSessionString(self::KEY_INTERVAL);
        $parameters = $service->current($date, $interval);
        $this->setSessionValue(self::KEY_INTERVAL, $parameters['interval']);
        $this->setSessionValue(self::KEY_DATE, $parameters['date']);

        return $this->renderTimeline($parameters);
    }

    /**
     * @throws \Exception
     */
    #[Get(path: '/first', name: '_first')]
    public function first(
        TimelineService $service,
        #[MapQueryParameter]
        ?string $interval = null
    ): Response {
        $interval ??= $this->getSessionString(self::KEY_INTERVAL);
        $parameters = $service->first($interval);
        $this->setSessionValue(self::KEY_INTERVAL, $parameters['interval']);

        return $this->renderTimeline($parameters);
    }

    /**
     * @throws \Exception
     */
    #[Get(path: '/last', name: '_last')]
    public function last(
        TimelineService $service,
        #[MapQueryParameter]
        ?string $interval = null
    ): Response {
        $interval ??= $this->getSessionString(self::KEY_INTERVAL);
        $parameters = $service->last($interval);
        $this->setSessionValue(self::KEY_INTERVAL, $parameters['interval']);

        return $this->renderTimeline($parameters);
    }

    private function renderTimeline(array $parameters): Response
    {
        return $this->render('test/timeline.html.twig', $parameters);
    }
}
