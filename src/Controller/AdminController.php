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

use App\Enums\EntityPermission;
use App\Form\Admin\ApplicationParametersType;
use App\Form\User\RoleRightsType;
use App\Interfaces\PropertyServiceInterface;
use App\Interfaces\RoleInterface;
use App\Model\Role;
use App\Service\SymfonyInfoService;
use App\Traits\RoleTranslatorTrait;
use App\Util\RoleBuilder;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller for administration tasks.
 */
#[AsController]
#[Route(path: '/admin')]
class AdminController extends AbstractController
{
    use RoleTranslatorTrait;

    /**
     * Clear the application cache.
     *
     * @throws \ReflectionException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[IsGranted(RoleInterface::ROLE_ADMIN)]
    #[Route(path: '/clear', name: 'admin_clear')]
    public function clearCache(Request $request, KernelInterface $kernel, LoggerInterface $logger, SymfonyInfoService $info): Response
    {
        // handle request
        $form = $this->createForm();
        if ($this->handleRequestForm($request, $form)) {
            // first clear user and application caches
            $this->getUserService()->clearCache();
            $this->getApplication()->clearCache();

            try {
                // new command
                $options = [
                    'command' => 'cache:pool:clear',
                    'pools' => ['cache.global_clearer'],
                    '--env' => $kernel->getEnvironment(),
                ];

                $input = new ArrayInput($options);
                $output = new BufferedOutput();

                $application = new Application($kernel);
                $application->setCatchExceptions(false);
                $application->setAutoExit(false);

                $result = $application->run($input, $output);
                $content = $output->fetch();

                $context = [
                    'result' => $result,
                    'options' => $options,
                    'content' => $content,
                ];
                $message = $this->successTrans('clear_cache.success');
                $logger->info($message, $context);

                return $this->redirectToHomePage();
            } catch (\Exception $e) {
                return $this->renderFormException('clear_cache.failure', $e, $logger);
            }
        }
        // display
        return $this->renderForm('admin/clear_cache.html.twig', [
            'size' => $info->getCacheSize(),
            'form' => $form,
        ]);
    }

    /**
     * Edit the application parameters.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[IsGranted(RoleInterface::ROLE_ADMIN)]
    #[Route(path: '/parameters', name: 'admin_parameters')]
    public function parameters(Request $request): Response
    {
        // properties
        $application = $this->getApplication();
        $data = $application->getProperties([
            PropertyServiceInterface::P_DATE_CALCULATION,
            PropertyServiceInterface::P_DATE_PRODUCT,
            PropertyServiceInterface::P_DATE_IMPORT,
        ]);

        // form
        $form = $this->createForm(ApplicationParametersType::class, $data);
        if ($this->handleRequestForm($request, $form)) {
            /** @psalm-var array<string, mixed> $data */
            $data = $form->getData();
            $application->setProperties($data);
            $this->successTrans('parameters.success');

            return $this->redirectToHomePage();
        }

        // display
        return $this->renderForm('admin/parameters.html.twig', [
            'options' => PropertyServiceInterface::PASSWORD_OPTIONS,
            'form' => $form,
        ]);
    }

    /**
     * Edit rights for the administrator role (@see RoleInterface::ROLE_ADMIN).
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
    #[Route(path: '/rights/admin', name: 'admin_rights_admin')]
    public function rightsAdmin(Request $request): Response
    {
        $roleName = RoleInterface::ROLE_ADMIN;
        $rights = $this->getApplication()->getAdminRights();
        $default = RoleBuilder::getRoleAdmin();
        $property = PropertyServiceInterface::P_ADMIN_RIGHTS;

        return $this->editRights($request, $roleName, $rights, $default, $property);
    }

    /**
     * Edit rights for the user role (@see RoleInterface::ROLE_USER).
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[IsGranted(RoleInterface::ROLE_ADMIN)]
    #[Route(path: '/rights/user', name: 'admin_rights_user')]
    public function rightsUser(Request $request): Response
    {
        $roleName = RoleInterface::ROLE_USER;
        $rights = $this->getApplication()->getUserRights();
        $default = RoleBuilder::getRoleUser();
        $property = PropertyServiceInterface::P_USER_RIGHTS;

        return $this->editRights($request, $roleName, $rights, $default, $property);
    }

    /**
     * Edit rights for the given role name.
     *
     * @param int[] $rights
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    private function editRights(Request $request, string $roleName, ?array $rights, Role $default, string $property): Response
    {
        // create role
        $role = new Role($roleName);
        $role->setName($this->translateRole($roleName))
            ->setRights($rights);

        // create and handle form
        $form = $this->createForm(RoleRightsType::class, $role);
        if ($this->handleRequestForm($request, $form)) {
            // update property
            $application = $this->getApplication();
            if ($role->getRights() === $default->getRights()) {
                $application->removeProperty($property);
            } else {
                $application->setProperty($property, $role->getRights());
            }
            $this->successTrans('admin.rights.success', ['%name%' => $role->getName()]);

            return $this->redirectToHomePage();
        }

        // show form
        return $this->renderForm('admin/role_rights.html.twig', [
            'form' => $form,
            'default' => $default,
            'is_admin' => $role->isAdmin(),
            'permissions' => EntityPermission::sorted(),
        ]);
    }
}
