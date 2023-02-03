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
use App\Service\ExchangeRateService;
use App\Util\FormatUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for the exchange rate service.
 */
#[AsController]
#[Route(path: '/exchange')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class ExchangeRateController extends AbstractController
{
    /**
     * Constructor.
     */
    public function __construct(private readonly ExchangeRateService $service)
    {
    }

    /**
     * Display the view.
     *
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Route(path: '', name: 'exchange_display')]
    public function display(): Response
    {
        return $this->render('test/exchangerate.html.twig', [
            'form' => $this->createForm(),
            'codes' => $this->service->getSupportedCodes(),
        ]);
    }

    /**
     * Gets the supported currency codes.
     *
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Route(path: '/codes', name: 'exchange_codes')]
    public function getCodes(): JsonResponse
    {
        $codes = $this->service->getSupportedCodes();
        if ($lastError = $this->service->getLastError()) {
            return $this->json($lastError);
        }

        return $this->json($codes);
    }

    /**
     * Gets the exchange rates from the given currency code to all the other currencies supported.
     *
     * @param string $code the base currency code
     *
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Route(path: '/latest/{code}', name: 'exchange_latest')]
    public function getLatest(string $code): JsonResponse
    {
        $latest = $this->service->getLatest($code);
        if ($lastError = $this->service->getLastError()) {
            return $this->json($lastError);
        }

        return $this->json($latest);
    }

    /**
     * Gets the exchange rate from the base currency code to the target currency code.
     *
     * @throws \ReflectionException
     * @throws \Symfony\Contracts\HttpClient\Exception\ExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    #[Route(path: '/rate', name: 'exchange_rate')]
    public function getRate(Request $request): JsonResponse
    {
        $baseCode = $this->getRequestString($request, 'baseCode', '');
        $targetCode = $this->getRequestString($request, 'targetCode', '');
        $result = $this->service->getRateAndDates($baseCode, $targetCode);
        if ($lastError = $this->service->getLastError()) {
            return $this->json($lastError);
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
            'message' => $this->trans('unknown', [], 'exchangerate'),
        ]);
    }
}
