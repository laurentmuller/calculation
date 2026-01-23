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
use App\Attribute\ForSuperAdmin;
use App\Attribute\GetPostRoute;
use App\Enums\EntityPermission;
use App\Form\User\RoleRightsType;
use App\Model\Role;
use App\Model\TranslatableFlashMessage;
use App\Parameter\ApplicationParameters;
use App\Service\RoleBuilderService;
use App\Service\RoleService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to manage users and administrators rights.
 */
#[ForAdmin]
#[Route(path: '/admin', name: 'admin_')]
class AdminRightsController extends AbstractController
{
    /**
     * Edit rights for the administrator role.
     */
    #[ForSuperAdmin]
    #[GetPostRoute(path: '/rights/admin', name: 'rights_admin')]
    public function rightsAdmin(
        Request $request,
        RoleService $roleService,
        RoleBuilderService $roleBuilderService
    ): Response {
        $parameters = $this->getApplicationParameters();
        $role = $parameters->getRights()->getAdminRole();
        $default = $roleBuilderService->getRoleAdmin();

        return $this->editRights($request, $parameters, $roleService, $role, $default);
    }

    /**
     * Edit rights for the user role.
     */
    #[GetPostRoute(path: '/rights/user', name: 'rights_user')]
    public function rightsUser(
        Request $request,
        RoleService $roleService,
        RoleBuilderService $roleBuilderService
    ): Response {
        $parameters = $this->getApplicationParameters();
        $role = $parameters->getRights()->getUserRole();
        $default = $roleBuilderService->getRoleUser();

        return $this->editRights($request, $parameters, $roleService, $role, $default);
    }

    private function editRights(
        Request $request,
        ApplicationParameters $parameters,
        RoleService $roleService,
        Role $role,
        Role $default
    ): Response {
        $form = $this->createForm(RoleRightsType::class, $role)
            ->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $rights = $role->getRights();
            if ($role->isAdmin()) {
                $parameters->getRights()->setAdminRights($rights);
            } else {
                $parameters->getRights()->setUserRights($rights);
            }

            if ($parameters->save()) {
                return $this->redirectToHomePage(
                    request: $request,
                    message: TranslatableFlashMessage::instance(
                        message: 'admin.rights.success',
                        parameters: ['%name%' => $roleService->translateRole($role)],
                    )
                );
            }

            return $this->redirectToHomePage(request: $request);
        }

        return $this->render('admin/role_rights.html.twig', [
            'form' => $form,
            'is_admin' => $role->isAdmin(),
            'default' => $default,
            'entities' => EntityPermission::sorted(),
        ]);
    }
}
