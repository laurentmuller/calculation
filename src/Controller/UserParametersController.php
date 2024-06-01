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

use App\Attribute\GetPost;
use App\Enums\TableView;
use App\Form\User\UserParametersType;
use App\Interfaces\PropertyServiceInterface;
use App\Interfaces\RoleInterface;
use App\Interfaces\TableInterface;
use App\Service\UserService;
use App\Traits\CookieTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to edit user preferences.
 */
#[AsController]
#[Route(path: '/user', name: 'user_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class UserParametersController extends AbstractController
{
    use CookieTrait;

    #[GetPost(path: '/parameters', name: 'parameters')]
    public function invoke(Request $request, UserService $userService): Response
    {
        $form = $this->createForm(UserParametersType::class, $userService->getProperties());
        if ($this->handleRequestForm($request, $form)) {
            /** @psalm-var array<string, mixed> $data */
            $data = $form->getData();
            if ($userService->setProperties($data)) {
                $this->successTrans('user.parameters.success');
            }
            $response = $this->getUrlGenerator()->redirect($request);
            if (isset($data[PropertyServiceInterface::P_DISPLAY_MODE])) {
                /** @psalm-var TableView $display */
                $display = $data[PropertyServiceInterface::P_DISPLAY_MODE];
                $this->updateCookie($response, TableInterface::PARAM_VIEW, $display, path: $this->getCookiePath());
            }

            return $response;
        }

        return $this->render('user/user_parameters.html.twig', [
            'form' => $form,
        ]);
    }
}
