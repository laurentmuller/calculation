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
use App\Attribute\GetRoute;
use App\Enums\EntityPermission;
use App\Enums\FlashType;
use App\Form\Parameters\ApplicationParametersType;
use App\Form\User\RoleRightsType;
use App\Interfaces\RoleInterface;
use App\Model\Role;
use App\Parameter\ApplicationParameters;
use App\Service\CacheService;
use App\Service\CommandService;
use App\Service\RoleBuilderService;
use App\Service\RoleService;
use App\Traits\EditParametersTrait;
use App\Utils\FileUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for administration tasks.
 */
#[Route(path: '/admin', name: 'admin_')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class AdminController extends AbstractController
{
    use EditParametersTrait;

    /**
     * Clear the application cache.
     */
    #[GetPostRoute(path: '/clear', name: 'clear')]
    public function clearCache(
        Request $request,
        KernelInterface $kernel,
        CacheService $service,
        LoggerInterface $logger
    ): Response {
        $form = $this->createForm(FormType::class);
        if ($this->handleRequestForm($request, $form)) {
            try {
                if ($service->clear()) {
                    return $this->redirectToHomePage('clear_cache.success', request: $request);
                }

                return $this->redirectToHomePage(
                    id: 'clear_cache.failure',
                    type: FlashType::DANGER,
                    request: $request
                );
            } catch (\Exception $e) {
                return $this->renderFormException('clear_cache.failure', $e, $logger);
            }
        }

        try {
            $pools = $service->list();
        } catch (\Exception) {
            $pools = [];
        }

        return $this->render('admin/clear_cache.html.twig', [
            'size' => FileUtils::formatSize($kernel->getCacheDir()),
            'pools' => $pools,
            'form' => $form,
        ]);
    }

    /**
     * Show SQL schema change.
     *
     * @throws \Exception
     */
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[GetRoute(path: '/dump-sql', name: 'dump_sql')]
    public function dumpSql(CommandService $service): Response
    {
        $result = $service->execute('doctrine:schema:update', ['--dump-sql' => true]);
        if (!$result->isSuccess()) {
            return $this->redirectToHomePage('admin.dump_sql.error', type: FlashType::WARNING);
        }

        if (\str_contains($result->content, '[OK]')) {
            return $this->redirectToHomePage('admin.dump_sql.no_change', type: FlashType::INFO);
        }

        return $this->render('admin/dump_sql.html.twig', [
            'count' => \substr_count($result->content, ';'),
            'content' => $result->content,
        ]);
    }

    /**
     * Edit the application parameters.
     */
    #[GetPostRoute(path: '/parameters', name: 'parameters')]
    public function parameters(Request $request): Response
    {
        $templateParameters = [
            'title_icon' => 'cogs',
            'title' => 'parameters.title',
            'title_description' => 'parameters.description',
        ];

        return $this->renderParameters(
            $request,
            $this->getApplicationParameters(),
            ApplicationParametersType::class,
            'parameters.success',
            $templateParameters
        );
    }

    /**
     * Edit rights for the administrator role.
     */
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[GetPostRoute(path: '/rights/admin', name: 'rights_admin')]
    public function rightsAdmin(
        Request $request,
        RoleService $roleService,
        RoleBuilderService $roleBuilderService
    ): Response {
        $parameters = $this->getApplicationParameters();
        $role = $parameters->getRights()->getAdminRole();
        $role->setName($roleService->translateRole($role));
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

    /**
     * Edit rights for the given role.
     */
    private function editRights(
        Request $request,
        ApplicationParameters $parameters,
        RoleService $roleService,
        Role $role,
        Role $default
    ): Response {
        $form = $this->createForm(RoleRightsType::class, $role);
        if ($this->handleRequestForm($request, $form)) {
            $rights = $role->getRights();
            if ($role->isAdmin()) {
                $parameters->getRights()->setAdminRights($rights);
            } else {
                $parameters->getRights()->setUserRights($rights);
            }

            if ($parameters->save()) {
                return $this->redirectToHomePage(
                    id: 'admin.rights.success',
                    parameters: ['%name%' => $roleService->translateRole($role)],
                    request: $request
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
