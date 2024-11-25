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
use App\Service\ExchangeRateService;
use App\Utils\FormatUtils;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for the exchange rate service.
 *
 * @psalm-import-type ExchangeRateAndDateType from ExchangeRateService
 */
#[AsController]
#[Route(path: '/exchange', name: 'exchange_')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class ExchangeRateController extends AbstractController
{
    public function __construct(private readonly ExchangeRateService $service)
    {
    }

    /**
     * Display the view.
     */
    #[Get(path: '', name: 'display')]
    public function display(): Response
    {
        return $this->render('test/exchange_rate.html.twig', [
            'form' => $this->createForm(FormType::class),
            'codes' => $this->service->getSupportedCodes(),
            'quota' => $this->service->getQuota(),
        ]);
    }

    /**
     * Gets the supported currency codes.
     *
     * @psalm-api
     */
    #[Get(path: '/codes', name: 'codes')]
    public function getCodes(): JsonResponse
    {
        $codes = $this->service->getSupportedCodes();
        if ($this->service->hasLastError()) {
            return $this->json($this->service->getLastError());
        }

        return $this->json($codes);
    }

    /**
     * Gets the exchange rates from the given currency code to all the other currencies supported.
     *
     * @param string $code the base currency code
     *
     * @psalm-api
     */
    #[Get(path: '/latest/{code}', name: 'latest')]
    public function getLatest(string $code): JsonResponse
    {
        $latest = $this->service->getLatest($code);
        if ($this->service->hasLastError()) {
            return $this->json($this->service->getLastError());
        }

        return $this->json($latest);
    }

    /**
     * Gets the exchange rate from the base currency code to the target currency code.
     *
     * @psalm-api
     */
    #[Get(path: '/rate', name: 'rate')]
    public function getRate(
        #[MapQueryParameter]
        string $baseCode = '',
        #[MapQueryParameter]
        string $targetCode = ''
    ): JsonResponse {
        $result = $this->service->getRateAndDates($baseCode, $targetCode);
        if ($this->service->hasLastError()) {
            return $this->json($this->service->getLastError());
        }
        if (\is_array($result)) {
            return $this->jsonTrue([
                'rate' => $result['rate'],
                'next' => FormatUtils::formatDateTime($result['next']),
                'update' => FormatUtils::formatDateTime($result['update']),
            ]);
        }

        return $this->jsonFalse([
            'code' => Response::HTTP_NOT_FOUND,
            'message' => $this->trans('unknown', [], 'exchange_rate'),
        ]);
    }
}
