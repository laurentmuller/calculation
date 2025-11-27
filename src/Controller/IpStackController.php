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
use App\Service\IpStackService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for IP stack service.
 */
#[ForSuperAdmin]
class IpStackController extends AbstractController
{
    #[GetRoute(path: '/ipstack', name: 'ipstack')]
    public function ipStack(Request $request, IpStackService $service): Response
    {
        $results = $service->getIpInfo($request);
        if ($service->hasLastError()) {
            return $this->render('bundles/TwigBundle/Exception/http_client_error.html.twig', [
                'error' => $service->getLastError(),
            ]);
        }

        return $this->render('test/ip_stack.html.twig', [
            'results' => $results,
        ]);
    }
}
