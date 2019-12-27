<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Form\FormHelper;
use App\Form\ParametersType;
use App\Form\RoleRightsType;
use App\Interfaces\IApplicationService;
use App\Security\EntityVoter;
use App\Service\SwissPostService;
use App\Utils\SymfonyUtils;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\File;

/**
 * Controller for administation tasks.
 *
 * @Route("/admin")
 */
class AdminController extends BaseController
{
    /**
     * Edit rights for the administrator role ('ROLE_ADMIN').
     *
     * @Route("/rights/admin", name="admin_rights_admin")
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function adminRights(Request $request): Response
    {
        // get values
        $roleName = User::ROLE_ADMIN;
        $rights = $this->getApplication()->getAdminRights();
        $default = EntityVoter::getRoleAdmin();
        $property = IApplicationService::ADMIN_RIGHTS;

        return $this->editRights($request, $roleName, $rights, $default, $property);
    }

    /**
     * Clear the application cache.
     *
     * @Route("/clear", name="admin_clear")
     * @IsGranted("ROLE_ADMIN")
     */
    public function clearCache(Request $request, KernelInterface $kernel, LoggerInterface $logger): Response
    {
        $form = $this->createFormBuilder()->getForm();
        if ($this->handleFormRequest($form, $request)) {
            // first clear application service cache
            $this->getApplication()->clearCache();

            try {
                $options = [
                    'command' => 'cache:clear',
                    '--env' => $kernel->getEnvironment(),
                    '--no-warmup' => true,
                ];

                $input = new ArrayInput($options);
                $output = new BufferedOutput();
                $application = new Application($kernel);
                $application->setCatchExceptions(false);
                $application->setAutoExit(false);
                $result = $application->run($input, $output);

                $context = [
                    'result' => $result,
                    'options' => $options,
                ];
                // $this->trans('clear_cache.success');
                $message = $this->succesTrans('clear_cache.success');
                $logger->info($message, $context);

                return  $this->redirectToHomePage();
            } catch (\Exception $e) {
                // show error
                $parameters = [
                    'exception' => $e,
                    'failure' => $this->trans('clear_cache.failure'),
                ];

                return $this->render('@Twig/Exception/exception.html.twig', $parameters);
            }
        }

        // display
        return $this->render('admin/clear_cache.html.twig', [
            'size' => SymfonyUtils::getCacheSize($kernel),
            'form' => $form->createView(),
        ]);
    }

    /**
     * Import streets and cities for Switerland.
     *
     * @Route("/import", name="admin_import")
     * @IsGranted("ROLE_ADMIN")
     */
    public function import(Request $request, SwissPostService $service): Response
    {
        // clear
        if ($this->getApplication()->isDebug()) {
            $this->getApplication()->clearCache();
        }

        // create form
        $builder = $this->createFormBuilder();
        $helper = new FormHelper($builder);

        // constraints
        $constraints = new File([
            'mimeTypes' => ['application/zip', 'application/x-zip-compressed'],
            'mimeTypesMessage' => $this->trans('import.error.mime_type'),
        ]
        );

        // fields
        $helper->field('file')
            ->label('import.file')
            ->updateOption('constraints', $constraints)
            ->updateAttribute('accept', 'application/x-zip-compressed')
            ->addFileType();

        // handle request
        $form = $builder->getForm();
        if ($this->handleFormRequest($form, $request)) {
            // import
            $file = $form->getData()['file'];
            $data = $service->setSourceFile($file)->import();

            // display result
            return $this->render('admin/import_result.html.twig', [
                'data' => $data,
            ]);
        }

        // display
        return $this->render('admin/import_file.html.twig', [
            'last_import' => $this->getApplication()->getLastImport(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * Display the application parameters.
     *
     * @Route("/parameters", name="admin_parameters")
     * @IsGranted("ROLE_ADMIN")
     */
    public function parameters(Request $request): Response
    {
        // properties
        $service = $this->getApplication();
        $data = $service->getProperties();

        // remove last update
        unset($data[IApplicationService::LAST_UPDATE], $data[IApplicationService::LAST_IMPORT]);

        // form
        $form = $this->createForm(ParametersType::class, $data);
        if ($this->handleFormRequest($form, $request)) {
            //save properties
            $data = $form->getData();
            $service->setProperties($data);
            $this->succesTrans('parameters.success');

            return  $this->redirectToHomePage();
        }

        // display
        return $this->render('admin/parameters.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Edit rights for the user role ('ROLE_USER').
     *
     * @Route("/rights/user", name="admin_rights_user")
     * @IsGranted("ROLE_ADMIN")
     */
    public function userRights(Request $request): Response
    {
        // get values
        $roleName = User::ROLE_DEFAULT;
        $rights = $this->getApplication()->getUserRights();
        $default = EntityVoter::getRoleUser();
        $property = IApplicationService::USER_RIGHTS;

        return $this->editRights($request, $roleName, $rights, $default, $property);
    }

    /**
     * Edit rights.
     *
     * @param Request $request  the request
     * @param string  $roleName the role name
     * @param string  $rights   the role rights
     * @param Role    $default  the role with default rights
     * @param string  $property the property name to update
     */
    private function editRights(Request $request, string $roleName, ?string $rights, Role $default, string $property): Response
    {
        // name
        $pos = \strpos($roleName, '_');
        $name = 'user.roles.' . \strtolower(\substr($roleName, $pos + 1));

        // role
        $role = new Role($roleName);
        $role->setName($this->trans($name))
            ->setRights($rights);

        // form
        $form = $this->createForm(RoleRightsType::class, $role);
        if ($this->handleFormRequest($form, $request)) {
            $this->getApplication()->setProperties([
                $property => $role->getRights(),
            ]);
            $this->succesTrans('admin.rights.success', ['%name%' => $role->getName()]);

            return  $this->redirectToHomePage();
        }

        // show form
        return $this->render('admin/role_rights.html.twig', [
            'form' => $form->createView(),
            'default' => $default,
        ]);
    }
}
