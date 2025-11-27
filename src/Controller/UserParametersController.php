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

use App\Attribute\ForUser;
use App\Attribute\GetPostRoute;
use App\Form\Parameters\UserParametersType;
use App\Traits\EditParametersTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to edit user preferences.
 */
#[ForUser]
#[Route(path: '/user', name: 'user_')]
class UserParametersController extends AbstractController
{
    use EditParametersTrait;

    #[GetPostRoute(path: '/parameters', name: 'parameters')]
    public function invoke(Request $request): Response
    {
        $templateParameters = [
            'title_icon' => 'user-gear',
            'title' => 'user.parameters.title',
            'title_description' => 'user.parameters.description',
        ];

        return $this->renderParameters(
            $request,
            $this->getUserParameters(),
            UserParametersType::class,
            'user.parameters.success',
            $templateParameters
        );
    }
}
