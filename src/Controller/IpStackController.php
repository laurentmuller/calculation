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
use App\Service\IpStackService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for IP stack service.
 */
#[AsController]
#[Route(path: '/ipstack')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class IpStackController extends AbstractController
{
    #[Route(path: '', name: 'ipstack')]
    public function ipStack(Request $request, IpStackService $service): Response
    {
        $results = $service->getIpInfo($request);
        if (null !== $error = $service->getLastError()) {
            return $this->render('bundles/TwigBundle/Exception/http_client_error.html.twig', [
                'error' => $error,
            ]);
        }

        return $this->render('test/ip_stack.html.twig', [
            'results' => $results,
        ]);
    }
}