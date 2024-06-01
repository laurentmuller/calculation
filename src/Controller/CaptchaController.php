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
use App\Form\Type\CaptchaImageType;
use App\Service\CaptchaImageService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\Voter\AuthenticatedVoter;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for captcha image validation.
 *
 * @see CaptchaImageType
 */
#[AsController]
#[Route(path: '/captcha', name: 'captcha_')]
#[IsGranted(AuthenticatedVoter::PUBLIC_ACCESS)]
class CaptchaController extends AbstractController
{
    /**
     * Returns a new captcha image.
     *
     * @throws \Exception
     */
    #[Get(path: '/image', name: 'image')]
    public function image(CaptchaImageService $service): JsonResponse
    {
        $data = $service->generateImage(true);
        if (null !== $data) {
            return $this->jsonTrue([
                'data' => $data,
            ]);
        }

        return $this->jsonFalse([
            'message' => $this->trans('captcha.generate', [], 'validators'),
        ]);
    }

    /**
     * Validate a captcha image.
     */
    #[Get(path: '/validate', name: 'validate')]
    public function validate(
        CaptchaImageService $service,
        #[MapQueryParameter]
        ?string $captcha = null
    ): JsonResponse {
        if (!$service->validateTimeout()) {
            $response = $this->trans('captcha.timeout', [], 'validators');
        } elseif (!$service->validateToken($captcha)) {
            $response = $this->trans('captcha.invalid', [], 'validators');
        } else {
            $response = true;
        }

        return $this->json($response);
    }
}
