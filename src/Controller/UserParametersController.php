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

use App\Attribute\GetPostRoute;
use App\Attribute\IsUser;
use App\Form\Parameters\UserParametersType;
use App\Traits\EditParametersTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to edit user preferences.
 */
#[IsUser]
#[Route(path: '/user', name: 'user_')]
class UserParametersController extends AbstractController
{
    use EditParametersTrait;

    #[GetPostRoute(path: '/parameters', name: 'parameters')]
    public function invoke(Request $request): Response
    {
        return $this->renderParameters(
            request: $request,
            parameters: $this->getUserParameters(),
            type: UserParametersType::class,
            template: 'parameters/user_parameters.html.twig',
            message: 'user.parameters.success'
        );
    }
}
