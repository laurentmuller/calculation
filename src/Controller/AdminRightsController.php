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
use App\Attribute\IsAdmin;
use App\Attribute\IsWebmaster;
use App\Form\User\RoleRightsType;
use App\Model\Role;
use App\Model\TranslatableFlashMessage;
use App\Parameter\ApplicationParameters;
use App\Parameter\RightsParameter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to manage the user and administrator rights.
 */
#[IsAdmin]
#[Route(path: '/admin', name: 'admin_')]
class AdminRightsController extends AbstractController
{
    /**
     * Edit rights for the administrator role.
     */
    #[IsWebmaster]
    #[GetPostRoute(path: '/rights/admin', name: 'rights_admin')]
    public function rightsAdmin(Request $request): Response
    {
        $parameters = $this->getApplicationParameters();
        $rights = $parameters->getRights();

        return $this->editRights(
            $request,
            $parameters,
            $rights->getAdminRole(),
            $rights->getDefaultAdminRole(),
            static fn (Role $role): RightsParameter => $rights->setAdminRights($role)
        );
    }

    /**
     * Edit rights for the user role.
     */
    #[GetPostRoute(path: '/rights/user', name: 'rights_user')]
    public function rightsUser(Request $request): Response
    {
        $parameters = $this->getApplicationParameters();
        $rights = $parameters->getRights();

        return $this->editRights(
            $request,
            $parameters,
            $rights->getUserRole(),
            $rights->getDefaultUserRole(),
            static fn (Role $role): RightsParameter => $rights->setUserRights($role)
        );
    }

    private function editRights(
        Request $request,
        ApplicationParameters $parameters,
        Role $role,
        Role $defaultRole,
        \Closure $save
    ): Response {
        $form = $this->createForm(RoleRightsType::class, $role);
        if ($this->handleRequestForm($request, $form)) {
            $save($role);
            if ($parameters->save()) {
                return $this->redirectToHomePage(
                    TranslatableFlashMessage::success(
                        message: 'admin.rights.success',
                        parameters: ['%name%' => $role],
                    )
                );
            }

            return $this->redirectToHomePage();
        }

        return $this->render('admin/role_rights.html.twig', [
            'form' => $form,
            'overwrite' => true,
            'permissions' => $defaultRole->getPermissions(),
        ]);
    }
}
