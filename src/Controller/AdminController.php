<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Role;
use App\Form\Admin\ParametersType;
use App\Form\User\RoleRightsType;
use App\Interfaces\ApplicationServiceInterface;
use App\Interfaces\RoleInterface;
use App\Security\EntityVoter;
use App\Service\CalculationUpdater;
use App\Service\ProductUpdater;
use App\Service\SwissPostService;
use App\Util\SymfonyInfo;
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
 * @author Laurent Muller
 *
 * @Route("/admin")
 */
class AdminController extends AbstractController
{
    /**
     * Clear the application cache.
     *
     * @Route("/clear", name="admin_clear")
     * @IsGranted("ROLE_ADMIN")
     */
    public function clearCache(Request $request, KernelInterface $kernel, LoggerInterface $logger, SymfonyInfo $info): Response
    {
        // handle request
        $form = $this->getForm();
        if ($this->handleRequestForm($request, $form)) {
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
                $message = $this->succesTrans('clear_cache.success');
                $logger->info($message, $context);

                return $this->redirectToHomePage();
            } catch (\Exception $e) {
                // show error
                $parameters = [
                    'exception' => $e,
                    'failure' => $this->trans('clear_cache.failure'),
                ];

                return $this->renderForm('@Twig/Exception/exception.html.twig', $parameters);
            }
        }

        // display
        return $this->renderForm('admin/clear_cache.html.twig', [
            'size' => $info->getCacheSize(),
            'form' => $form,
        ]);
    }

    /**
     * Import streets and cities for Switzerland.
     *
     * @Route("/import", name="admin_import")
     * @IsGranted("ROLE_ADMIN")
     */
    public function import(Request $request, SwissPostService $service): Response
    {
        // clear
        if ($this->getApplication()->getDebug()) {
            $this->getApplication()->clearCache();
        }

        // create form
        $helper = $this->createFormHelper();

        // constraints
        $constraints = new File([
            'mimeTypes' => ['application/zip', 'application/x-zip-compressed'],
            'mimeTypesMessage' => $this->trans('import.error.mime_type'),
        ]);

        // fields
        $helper->field('file')
            ->label('import.file')
            ->updateOption('constraints', $constraints)
            ->updateAttribute('accept', 'application/x-zip-compressed')
            ->addFileType();

        // handle request
        $form = $helper->createForm();
        if ($this->handleRequestForm($request, $form)) {
            // import
            $file = $form->getData()['file'];
            $data = $service->setSourceFile($file)->import();

            // display result
            return $this->renderForm('admin/import_result.html.twig', [
                'data' => $data,
            ]);
        }

        // display
        return $this->renderForm('admin/import_file.html.twig', [
            'last_import' => $this->getApplication()->getLastImport(),
            'form' => $form,
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
        $data = $service->getProperties([
            ApplicationServiceInterface::P_UPDATE_CALCULATIONS,
            ApplicationServiceInterface::P_UPDATE_PRODUCTS,
            ApplicationServiceInterface::P_LAST_IMPORT,
        ]);

        // password options
        foreach (ParametersType::PASSWORD_OPTIONS as $option) {
            $data[$option] = $service->isPropertyBoolean($option);
        }

        // form
        $form = $this->createForm(ParametersType::class, $data);
        if ($this->handleRequestForm($request, $form)) {
            //save properties
            $data = $form->getData();
            $service->setProperties($data);
            $this->succesTrans('parameters.success');

            return $this->redirectToHomePage();
        }

        // display
        return $this->renderForm('admin/parameters.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Edit rights for the administrator role ('ROLE_ADMIN').
     *
     * @Route("/rights/admin", name="admin_rights_admin")
     * @IsGranted("ROLE_SUPER_ADMIN")
     */
    public function rightsAdmin(Request $request): Response
    {
        // get values
        $roleName = RoleInterface::ROLE_ADMIN;
        $rights = $this->getApplication()->getAdminRights();
        $default = EntityVoter::getRoleAdmin();
        $property = ApplicationServiceInterface::P_ADMIN_RIGHTS;

        return $this->editRights($request, $roleName, $rights, $default, $property);
    }

    /**
     * Edit rights for the user role ('ROLE_USER').
     *
     * @Route("/rights/user", name="admin_rights_user")
     * @IsGranted("ROLE_ADMIN")
     */
    public function rightsUser(Request $request): Response
    {
        // get values
        $roleName = RoleInterface::ROLE_USER;
        $rights = $this->getApplication()->getUserRights();
        $default = EntityVoter::getRoleUser();
        $property = ApplicationServiceInterface::P_USER_RIGHTS;

        return $this->editRights($request, $roleName, $rights, $default, $property);
    }

    /**
     * Update calculations.
     *
     * @Route("/calculation", name="admin_calculation")
     * @IsGranted("ROLE_ADMIN")
     */
    public function updateCalculation(Request $request, CalculationUpdater $updater): Response
    {
        // create form
        $form = $updater->createForm();

        // handle request
        if ($this->handleRequestForm($request, $form)) {
            $data = $form->getData();
            $includeClosed = (bool) $data['closed'];
            $includeSorted = (bool) $data['sorted'];
            $includeEmpty = (bool) $data['empty'];
            $includeDuplicated = (bool) $data['duplicated'];
            $simulated = (bool) $data['simulated'];

            $results = $updater->update($includeClosed, $includeSorted, $includeEmpty, $includeDuplicated, $simulated);

            // update last update
            if ($results['result'] && !$simulated) {
                $this->getApplication()->setProperties([ApplicationServiceInterface::P_UPDATE_CALCULATIONS => new \DateTime()]);
            }

            return $this->renderForm('calculation/calculation_result.html.twig', $results);
        }

        // display
        return $this->renderForm('calculation/calculation_update.html.twig', [
            'last_update' => $this->getApplication()->getUpdateCalculations(),
            'form' => $form,
        ]);
    }

    /**
     * Update calculation totals.
     *
     * @Route("/product", name="admin_product")
     * @IsGranted("ROLE_ADMIN")
     */
    public function updateProduct(Request $request, ProductUpdater $updater): Response
    {
        // create form
        $form = $updater->createForm();

        // handle request
        if ($this->handleRequestForm($request, $form)) {
            $data = $form->getData();
            $category = $data['category'];
            $percent = (float) $data['percent'];
            $fixed = (float) $data['fixed'];
            $isPercent = ProductUpdater::UPDATE_PERCENT === $data['type'];
            $round = (bool) $data['round'];
            $simulated = (bool) $data['simulated'];
            $value = $isPercent ? $percent : $fixed;

            $results = $updater->update($category, $value, $isPercent, $round, $simulated);

            // update last update
            if ($results['result'] && !$simulated) {
                $this->getApplication()->setProperties([ApplicationServiceInterface::P_UPDATE_PRODUCTS => new \DateTime()]);
            }

            return $this->renderForm('product/product_result.html.twig', $results);
        }

        return $this->renderForm('product/product_update.html.twig', [
            'last_update' => $this->getApplication()->getUpdateProducts(),
            'products' => $updater->getAllProducts(),
            'form' => $form,
        ]);
    }

    /**
     * Edit rights.
     *
     * @param Request $request  the request
     * @param string  $roleName the role name
     * @param int[]   $rights   the role rights
     * @param Role    $default  the role with default rights
     * @param string  $property the property name to update
     */
    private function editRights(Request $request, string $roleName, ?array $rights, Role $default, string $property): Response
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
        if ($this->handleRequestForm($request, $form)) {
            $this->getApplication()->setProperties([
                $property => $role->getRights(),
            ]);
            $this->succesTrans('admin.rights.success', ['%name%' => $role->getName()]);

            return $this->redirectToHomePage();
        }

        // show form
        return $this->renderForm('admin/role_rights.html.twig', [
            'form' => $form,
            'default' => $default,
        ]);
    }
}
