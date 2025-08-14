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
use App\Form\Admin\ApplicationParametersType;
use App\Form\User\RoleRightsType;
use App\Interfaces\PropertyServiceInterface;
use App\Interfaces\RoleInterface;
use App\Model\Role;
use App\Service\ApplicationService;
use App\Service\CacheService;
use App\Service\CommandService;
use App\Service\RoleBuilderService;
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
        $application = $this->getApplicationService();
        $form = $this->createForm(ApplicationParametersType::class, $application->getProperties());
        if ($this->handleRequestForm($request, $form)) {
            /** @phpstan-var array<string, mixed> $data */
            $data = $form->getData();
            if ($application->setProperties($data)) {
                return $this->redirectToHomePage('parameters.success', request: $request);
            }

            return $this->redirectToHomePage();
        }

        return $this->render('admin/parameters.html.twig', [
            'options' => \array_keys(PropertyServiceInterface::PASSWORD_OPTIONS),
            'form' => $form,
        ]);
    }

    /**
     * Edit rights for the administrator role.
     */
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[GetPostRoute(path: '/rights/admin', name: 'rights_admin')]
    public function rightsAdmin(Request $request, RoleBuilderService $service): Response
    {
        $application = $this->getApplicationService();
        $role = $application->getAdminRole();
        $default = $service->getRoleAdmin();

        return $this->editRights(
            $request,
            $role,
            $default,
            $application,
            PropertyServiceInterface::P_ADMIN_RIGHTS
        );
    }

    /**
     * Edit rights for the user role.
     */
    #[GetPostRoute(path: '/rights/user', name: 'rights_user')]
    public function rightsUser(Request $request, RoleBuilderService $service): Response
    {
        $application = $this->getApplicationService();
        $role = $application->getUserRole();
        $default = $service->getRoleUser();

        return $this->editRights(
            $request,
            $role,
            $default,
            $application,
            PropertyServiceInterface::P_USER_RIGHTS
        );
    }

    /**
     * Edit rights for the given role.
     */
    private function editRights(
        Request $request,
        Role $role,
        Role $default,
        ApplicationService $application,
        string $property
    ): Response {
        $form = $this->createForm(RoleRightsType::class, $role);
        if ($this->handleRequestForm($request, $form)) {
            $rights = $role->getRights();
            if ($rights === $default->getRights()) {
                $result = $application->removeProperty($property);
            } else {
                $result = $application->setProperty($property, $rights);
            }

            if ($result) {
                return $this->redirectToHomePage(
                    id: 'admin.rights.success',
                    parameters: ['%name%' => $role->getName()],
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
