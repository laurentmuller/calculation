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
 * Controller to manage the user and administrator rights.
 */
#[ForAdmin]
#[Route(path: '/admin', name: 'admin_')]
class AdminRightsController extends AbstractController
{
    public function __construct(
        private readonly RoleService $roleService,
        private readonly RoleBuilderService $roleBuilderService
    ) {
    }

    /**
     * Edit rights for the administrator role.
     */
    #[ForSuperAdmin]
    #[GetPostRoute(path: '/rights/admin', name: 'rights_admin')]
    public function rightsAdmin(Request $request): Response
    {
        $parameters = $this->getApplicationParameters();
        $role = $parameters->getRights()->getAdminRole();
        $default = $this->roleBuilderService->getRoleAdmin();

        return $this->editRights($request, $parameters, $role, $default);
    }

    /**
     * Edit rights for the user role.
     */
    #[GetPostRoute(path: '/rights/user', name: 'rights_user')]
    public function rightsUser(Request $request): Response
    {
        $parameters = $this->getApplicationParameters();
        $role = $parameters->getRights()->getUserRole();
        $default = $this->roleBuilderService->getRoleUser();

        return $this->editRights($request, $parameters, $role, $default);
    }

    private function editRights(
        Request $request,
        ApplicationParameters $parameters,
        Role $role,
        Role $default
    ): Response {
        $form = $this->createForm(RoleRightsType::class, $role);
        if ($this->handleRequestForm($request, $form)) {
            $rights = $parameters->getRights();
            if ($role->isAdmin()) {
                $rights->setAdminRights($role->getRights());
            } else {
                $rights->setUserRights($role->getRights());
            }
            if ($parameters->save()) {
                return $this->redirectToHomePage(
                    request: $request,
                    message: TranslatableFlashMessage::success(
                        message: 'admin.rights.success',
                        parameters: ['%name%' => $this->roleService->translateRole($role)],
                    )
                );
            }

            return $this->redirectToHomePage(request: $request);
        }

        return $this->render('admin/role_rights.html.twig', [
            'form' => $form,
            'default' => $default,
            'headers' => EntityPermission::sorted(),
        ]);
    }
}
