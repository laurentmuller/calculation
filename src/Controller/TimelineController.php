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

use App\Attribute\ForSuperAdmin;
use App\Attribute\GetRoute;
use App\Attribute\IndexRoute;
use App\Service\TimelineService;
use App\Utils\DateUtils;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to display calculations timeline.
 *
 * @phpstan-import-type ParametersType from TimelineService
 */
#[ForSuperAdmin]
#[Route(path: '/timeline', name: 'timeline_')]
class TimelineController extends AbstractController
{
    private const string KEY_DATE = 'timeline_date';
    private const string KEY_INTERVAL = 'timeline_interval';

    #[GetRoute(path: '/content', name: 'content')]
    public function content(
        TimelineService $service,
        #[MapQueryParameter]
        string $date,
        #[MapQueryParameter]
        string $interval
    ): JsonResponse {
        $parameters = $service->current($date, $interval);

        return $this->renderContent($parameters);
    }

    #[GetRoute(path: '/first', name: 'first')]
    public function first(
        TimelineService $service,
        #[MapQueryParameter]
        string $interval
    ): JsonResponse {
        $parameters = $service->first($interval);

        return $this->renderContent($parameters);
    }

    #[IndexRoute]
    public function index(
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

        return $this->render('test/timeline.html.twig', $parameters);
    }

    #[GetRoute(path: '/last', name: 'last')]
    public function last(
        TimelineService $service,
        #[MapQueryParameter]
        string $interval
    ): JsonResponse {
        $parameters = $service->last($interval);

        return $this->renderContent($parameters);
    }

    #[GetRoute(path: '/today', name: 'today')]
    public function today(
        TimelineService $service,
        #[MapQueryParameter]
        string $interval
    ): JsonResponse {
        $date = DateUtils::createDatePoint('today');
        $parameters = $service->current($this->formatDate($date), $interval);

        return $this->renderContent($parameters);
    }

    private function formatDate(DatePoint $date): string
    {
        return $date->format(TimelineService::DATE_FORMAT);
    }

    /**
     * @phpstan-param ParametersType $parameters
     */
    private function renderContent(array $parameters): JsonResponse
    {
        $this->setSessionValue(self::KEY_DATE, $parameters['date']);
        $this->setSessionValue(self::KEY_INTERVAL, $parameters['interval']);
        $content = $this->renderView('test/_timeline_content.html.twig', $parameters);

        return $this->jsonTrue([
            'date' => $parameters['date'],
            'from' => $this->formatDate($parameters['from']),
            'to' => $this->formatDate($parameters['to']),
            'previous' => $parameters['previous'],
            'next' => $parameters['next'],
            'content' => $content,
        ]);
    }
}
