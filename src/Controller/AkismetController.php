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
use App\Service\AkismetService;
use App\Service\FakerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for the Askimet service.
 */
#[AsController]
#[Route(path: '/akismet', name: 'akismet')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class AkismetController extends AbstractController
{
    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     *
     * @psalm-api
     */
    #[Get(path: '/spam', name: '_spam')]
    public function spam(AkismetService $service, FakerService $faker): JsonResponse
    {
        $comment = $faker->getGenerator()->realText(145);
        $results = $service->verifyComment($comment);
        if ($service->hasLastError()) {
            return $this->json($service->getLastError());
        }

        return $this->json([
            'comment' => $comment,
            'spam' => $results,
        ]);
    }

    /**
     * @psalm-api
     */
    #[Get(path: '/verify', name: '_verify')]
    public function verify(AkismetService $service): JsonResponse
    {
        $results = $service->verifyKey();
        if ($service->hasLastError()) {
            return $this->json($service->getLastError());
        }

        return $this->json(['key_valid' => $results]);
    }
}
