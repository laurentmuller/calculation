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

use App\Attribute\ForPublicAccess;
use App\Attribute\GetRoute;
use App\Form\Type\CaptchaImageType;
use App\Service\CaptchaImageService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for captcha image validation.
 *
 * @see CaptchaImageType
 */
#[ForPublicAccess]
#[Route(path: '/captcha', name: 'captcha_')]
class CaptchaController extends AbstractController
{
    /**
     * Returns a new captcha image.
     *
     * @throws \Exception
     */
    #[GetRoute(path: '/image', name: 'image')]
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
    #[GetRoute(path: '/validate', name: 'validate')]
    public function validate(
        CaptchaImageService $service,
        #[MapQueryParameter]
        ?string $captcha = null
    ): JsonResponse {
        if (!$service->validateTimeout()) {
            return $this->json($this->trans('captcha.timeout', [], 'validators'));
        }
        if (!$service->validateToken($captcha)) {
            return $this->json($this->trans('captcha.invalid', [], 'validators'));
        }

        return $this->json(true);
    }
}
