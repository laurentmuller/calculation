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
use App\Service\AkismetService;
use App\Service\FakerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for the Askimet service.
 */
#[AsController]
#[Route(path: '/akismet')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class AkismetController extends AbstractController
{
    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     */
    #[Route(path: '/spam', name: 'akismet_comment')]
    public function verifyComment(AkismetService $service, FakerService $faker): JsonResponse
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

    #[Route(path: '/verify', name: 'akismet_key')]
    public function verifyKey(AkismetService $service): JsonResponse
    {
        $results = $service->verifyKey();
        if ($service->hasLastError()) {
            return $this->json($service->getLastError());
        }

        return $this->json(['key_valid' => $results]);
    }
}