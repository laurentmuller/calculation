<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Controller;

use App\Service\ExchangeRateService;
use App\Util\FormatUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for the exchange rate service.
 *
 * @author Laurent Muller
 *
 * @Route("/exchange")
 * @IsGranted("ROLE_SUPER_ADMIN")
 */
class ExchangeRateController extends AbstractController
{
    /**
     * The service.
     */
    private ExchangeRateService $service;

    /**
     * Constructor.
     */
    public function __construct(ExchangeRateService $service)
    {
        $this->service = $service;
    }

    /**
     * Display the view.
     *
     * @Route("", name="exchange_display")
     */
    public function display(): Response
    {
        return $this->render('test/exchangerate.html.twig', [
            'form' => $this->getForm()->createView(),
            'codes' => $this->service->getSupportedCodes(),
        ]);
    }

    /**
     * Gets the supported currency codes.
     *
     * @Route("/codes", name="exchange_codes")
     */
    public function getCodes(): JsonResponse
    {
        $codes = $this->service->getSupportedCodes();
        if ($lastError = $this->service->getLastError()) {
            return new JsonResponse($lastError);
        }

        return $this->json($codes);
    }

    /**
     * Gets the exchange rates from the given curreny code to all the other currencies supported.
     *
     * @param string $code the base curreny code
     *
     * @Route("/latest/{code}", name="exchange_latest")
     */
    public function getLatest(string $code): JsonResponse
    {
        $latest = $this->service->getLatest($code);
        if ($lastError = $this->service->getLastError()) {
            return new JsonResponse($lastError);
        }

        return $this->json($latest);
    }

    /**
     * Gets the exchange rate from the base curreny code to the target currency code.
     *
     * @Route("/rate", name="exchange_rate")
     */
    public function getRate(Request $request): JsonResponse
    {
        $baseCode = (string) $request->get('baseCode', '');
        $targetCode = (string) $request->get('targetCode', '');
        $result = $this->service->getRateAndDates($baseCode, $targetCode);

        if ($lastError = $this->service->getLastError()) {
            return $this->json([
                'result' => false,
                'message' => $lastError['message'],
            ]);
        }

        return $this->json([
            'result' => true,
            'rate' => $result['rate'],
            'next' => FormatUtils::formatDateTime($result['next']),
            'update' => FormatUtils::formatDateTime($result['update']),
        ]);
    }
}
