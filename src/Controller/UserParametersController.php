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
use App\Form\Parameters\UserParametersType;
use App\Interfaces\RoleInterface;
use App\Traits\EditParametersTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to edit user preferences.
 */
#[Route(path: '/user', name: 'user_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class UserParametersController extends AbstractController
{
    use EditParametersTrait;

    #[GetPostRoute(path: '/parameters', name: 'parameters')]
    public function invoke(Request $request): Response
    {
        $templateParameters = [
            'title' => 'user.parameters.title',
            'title_icon' => 'user-gear',
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
