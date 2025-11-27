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
use App\Service\AkismetService;
use App\Service\FakerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

/**
 * Controller for the Askimet service.
 */
#[ForSuperAdmin]
#[Route(path: '/akismet', name: 'akismet_')]
class AkismetController extends AbstractController
{
    /**
     * @throws ExceptionInterface
     */
    #[GetRoute(path: '/activity', name: 'activity')]
    public function activity(
        AkismetService $service,
        #[MapQueryParameter]
        ?int $year = null,
        #[MapQueryParameter]
        ?int $month = null,
    ): JsonResponse {
        $results = $service->activity($year, $month);
        if ($service->hasLastError()) {
            return $this->json($service->getLastError());
        }

        return $this->json($results);
    }

    /**
     * @throws ExceptionInterface
     */
    #[GetRoute(path: '/spam', name: 'spam')]
    public function spam(
        Request $request,
        AkismetService $service,
        FakerService $faker,
        #[MapQueryParameter]
        ?string $comment = null,
    ): JsonResponse {
        $comment ??= $faker->getGenerator()->realText(145);
        $results = $service->isSpam($comment, [], $request);
        if ($service->hasLastError()) {
            return $this->json($service->getLastError());
        }

        return $this->json([
            'comment' => $comment,
            'spam' => $results,
        ]);
    }

    /**
     * @throws ExceptionInterface
     */
    #[GetRoute(path: '/usage', name: 'usage')]
    public function usage(AkismetService $service): JsonResponse
    {
        $results = $service->usage();
        if ($service->hasLastError()) {
            return $this->json($service->getLastError());
        }

        return $this->json($results);
    }

    #[GetRoute(path: '/verify', name: 'verify')]
    public function verify(AkismetService $service): JsonResponse
    {
        $result = $service->isValidKey();
        if ($service->hasLastError()) {
            return $this->json($service->getLastError());
        }

        return $this->json(['valid' => $result]);
    }
}
