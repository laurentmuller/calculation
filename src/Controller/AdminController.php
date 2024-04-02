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
use App\Enums\EntityPermission;
use App\Enums\FlashType;
use App\Form\Admin\ApplicationParametersType;
use App\Form\User\RoleRightsType;
use App\Interfaces\PropertyServiceInterface;
use App\Interfaces\RoleInterface;
use App\Model\Role;
use App\Service\ApplicationService;
use App\Service\CacheService;
use App\Service\RoleBuilderService;
use App\Traits\RoleTranslatorTrait;
use App\Utils\FileUtils;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for administration tasks.
 */
#[AsController]
#[Route(path: '/admin')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class AdminController extends AbstractController
{
    use RoleTranslatorTrait;

    /**
     * Clear the application cache.
     */
    #[GetPost(path: '/clear', name: 'admin_clear')]
    public function clearCache(
        Request $request,
        KernelInterface $kernel,
        CacheService $service,
        LoggerInterface $logger
    ): Response {
        $form = $this->createForm(FormType::class);
        if ($this->handleRequestForm($request, $form)) {
            $this->getUserService()->clearCache();
            $this->getApplication()->clearCache();

            try {
                if ($service->clear()) {
                    return $this->redirectToHomePage('clear_cache.success', request: $request);
                }

                return $this->redirectToHomePage(
                    message: 'clear_cache.failure',
                    type: FlashType::DANGER,
                    request: $request
                );
            } catch (\Exception $e) {
                return $this->renderFormException('clear_cache.failure', $e, $logger);
            }
        }

        try {
            $pools = $service->list();
        } catch (\Exception $e) {
            return $this->renderFormException('clear_cache.failure', $e, $logger);
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
    #[GetPost(path: '/dump-sql', name: 'admin_dump_sql')]
    public function dumpSql(KernelInterface $kernel): Response
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'doctrine:schema:update',
            '--dump-sql' => true,
        ]);
        $output = new BufferedOutput();

        $application->run($input, $output);
        $content = $output->fetch();

        if ('' === $content) {
            return $this->redirectToHomePage('admin.dump_sql.no_change', type: FlashType::INFO);
        }

        return $this->render('admin/dump_sql.html.twig', ['content' => $content]);
    }

    /**
     * Edit the application parameters.
     */
    #[GetPost(path: '/parameters', name: 'admin_parameters')]
    public function parameters(Request $request): Response
    {
        $application = $this->getApplication();
        $data = $application->getProperties();
        $form = $this->createForm(ApplicationParametersType::class, $data);
        if ($this->handleRequestForm($request, $form)) {
            /** @psalm-var array<string, mixed> $data */
            $data = $form->getData();
            if ($application->setProperties($data)) {
                return $this->redirectToHomePage('parameters.success', request: $request);
            }

            return $this->redirectToHomePage();
        }

        return $this->render('admin/parameters.html.twig', [
            'options' => PropertyServiceInterface::PASSWORD_OPTIONS,
            'form' => $form,
        ]);
    }

    /**
     * Edit rights for the administrator role (@see RoleInterface::ROLE_ADMIN).
     */
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[GetPost(path: '/rights/admin', name: 'admin_rights_admin')]
    public function rightsAdmin(Request $request, RoleBuilderService $service): Response
    {
        $application = $this->getApplication();
        $roleName = RoleInterface::ROLE_ADMIN;
        $rights = $application->getAdminRights();
        $default = $service->getRoleAdmin();
        $property = PropertyServiceInterface::P_ADMIN_RIGHTS;

        return $this->editRights($request, $application, $roleName, $rights, $default, $property);
    }

    /**
     * Edit rights for the user role (@see RoleInterface::ROLE_USER).
     */
    #[GetPost(path: '/rights/user', name: 'admin_rights_user')]
    public function rightsUser(Request $request, RoleBuilderService $service): Response
    {
        $application = $this->getApplication();
        $roleName = RoleInterface::ROLE_USER;
        $rights = $application->getUserRights();
        $default = $service->getRoleUser();
        $property = PropertyServiceInterface::P_USER_RIGHTS;

        return $this->editRights($request, $application, $roleName, $rights, $default, $property);
    }

    /**
     * @psalm-param int[] $rights
     */
    private function createRole(string $roleName, array $rights): Role
    {
        $role = new Role($roleName);

        return $role->setName($this->translateRole($roleName))
            ->setRights($rights);
    }

    /**
     * Edit rights for the given role name.
     *
     * @param int[] $rights
     */
    private function editRights(
        Request $request,
        ApplicationService $application,
        string $roleName,
        array $rights,
        Role $default,
        string $property
    ): Response {
        $role = $this->createRole($roleName, $rights);
        $form = $this->createForm(RoleRightsType::class, $role);
        if ($this->handleRequestForm($request, $form)) {
            if ($role->getRights() === $default->getRights()) {
                $application->removeProperty($property);
            } else {
                $application->setProperty($property, $role->getRights());
            }

            return $this->redirectToHomePage(
                'admin.rights.success',
                ['%name%' => $role->getName()],
                request: $request
            );
        }

        return $this->render('admin/role_rights.html.twig', [
            'form' => $form,
            'default' => $default,
            'is_admin' => $role->isAdmin(),
            'permissions' => EntityPermission::sorted(),
        ]);
    }
}
