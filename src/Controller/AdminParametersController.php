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

use App\Attribute\ForAdmin;
use App\Attribute\GetPostRoute;
use App\Form\Parameters\ApplicationParametersType;
use App\Traits\EditParametersTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to edit application preferences.
 */
#[ForAdmin]
#[Route(path: '/admin', name: 'admin_')]
class AdminParametersController extends AbstractController
{
    use EditParametersTrait;

    #[GetPostRoute(path: '/parameters', name: 'parameters')]
    public function parameters(Request $request): Response
    {
        return $this->renderParameters(
            request: $request,
            parameters: $this->getApplicationParameters(),
            type: ApplicationParametersType::class,
            template: 'parameters/admin_parameters.html.twig',
            message: 'parameters.success'
        );
    }
}
