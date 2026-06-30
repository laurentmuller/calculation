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
use App\Form\User\RoleRightsType;
use App\Model\Role;
use App\Model\TranslatableFlashMessage;
use App\Parameter\ApplicationParameters;
use App\Service\RoleBuilderService;
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
    public function __construct(private readonly RoleBuilderService $service)
    {
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
        $defaultRole = $this->service->getAdminRole();

        return $this->editRights($request, $parameters, $role, $defaultRole);
    }

    /**
     * Edit rights for the user role.
     */
    #[GetPostRoute(path: '/rights/user', name: 'rights_user')]
    public function rightsUser(Request $request): Response
    {
        $parameters = $this->getApplicationParameters();
        $role = $parameters->getRights()->getUserRole();
        $defaultRole = $this->service->getUserRole();

        return $this->editRights($request, $parameters, $role, $defaultRole);
    }

    private function editRights(
        Request $request,
        ApplicationParameters $parameters,
        Role $role,
        Role $defaultRole
    ): Response {
        $form = $this->createForm(RoleRightsType::class, $role);
        if ($this->handleRequestForm($request, $form)) {
            $parameters->getRights()
                ->setRightsFromRole($role);
            if ($parameters->save()) {
                return $this->redirectToHomePage(
                    request: $request,
                    message: TranslatableFlashMessage::success(
                        message: 'admin.rights.success',
                        parameters: ['%name%' => $role],
                    )
                );
            }

            return $this->redirectToHomePage(request: $request);
        }

        return $this->render('admin/role_rights.html.twig', [
            'form' => $form,
            'permissions' => $defaultRole->getPermissions(),
            'overwrite' => true,
        ]);
    }
}
