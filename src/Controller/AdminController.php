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

use App\Entity\Calculation;
use App\Entity\Role;
use App\Form\Admin\ParametersType;
use App\Form\FormHelper;
use App\Form\User\RoleRightsType;
use App\Interfaces\ApplicationServiceInterface;
use App\Interfaces\RoleInterface;
use App\Repository\CalculationRepository;
use App\Security\EntityVoter;
use App\Service\CalculationService;
use App\Service\SuspendEventListenerService;
use App\Service\SwissPostService;
use App\Util\SymfonyUtils;
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
class AdminController extends AbstractController
{
    /**
     * Clear the application cache.
     *
     * @Route("/clear", name="admin_clear")
     * @IsGranted("ROLE_ADMIN")
     */
    public function clearCache(Request $request, KernelInterface $kernel, LoggerInterface $logger): Response
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
        $data = $service->getProperties([
            ApplicationServiceInterface::P_LAST_UPDATE,
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

            return  $this->redirectToHomePage();
        }

        // display
        return $this->render('admin/parameters.html.twig', [
            'form' => $form->createView(),
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
     * Update calculation totals.
     *
     * @Route("/update", name="admin_update", methods={"GET", "POST"})
     * @IsGranted("ROLE_ADMIN")
     */
    public function update(Request $request, CalculationRepository $repository, CalculationService $service, LoggerInterface $logger, SuspendEventListenerService $listener): Response
    {
        // create form helper
        $helper = $this->createUpdateHelper();

        // handle request
        $form = $helper->createForm();
        if ($this->handleRequestForm($request, $form)) {
            $data = $form->getData();
            $includeClosed = (bool) $data['closed'];
            $includeSorted = (bool) $data['sorted'];
            $includeEmpty = (bool) $data['empty'];
            $includeDuplicated = (bool) $data['duplicated'];
            $isSimulated = (bool) $data['simulated'];

            $updated = 0;
            $skipped = 0;
            $empty = 0;
            $duplicated = 0;
            $sorted = 0;
            $unmodifiable = 0;

            try {
                $listener->disableListeners();

                /** @var Calculation[] $calculations */
                $calculations = $repository->findAll();
                foreach ($calculations as $calculation) {
                    if ($includeClosed || $calculation->isEditable()) {
                        $changed = false;
                        if ($includeEmpty && $calculation->hasEmptyItems()) {
                            $empty += $calculation->removeEmptyItems();
                            $changed = true;
                        }
                        if ($includeDuplicated && $calculation->hasDuplicateItems()) {
                            $duplicated += $calculation->removeDuplicateItems();
                            $changed = true;
                        }
                        if ($includeSorted && $calculation->sort()) {
                            ++$sorted;
                            $changed = true;
                        }
                        if ($service->updateTotal($calculation) || $changed) {
                            ++$updated;
                        } else {
                            ++$skipped;
                        }
                    } else {
                        ++$unmodifiable;
                    }
                }

                if ($updated > 0 && !$isSimulated) {
                    $this->getManager()->flush();
                }
            } finally {
                $listener->enableListeners();
            }

            $total = \count($calculations);

            if (!$isSimulated) {
                // update last update
                $this->getApplication()->setProperties([ApplicationServiceInterface::P_LAST_UPDATE => new \DateTime()]);

                // log results
                $context = [
                    $this->trans('calculation.result.empty') => $empty,
                    $this->trans('calculation.result.duplicated') => $duplicated,
                    $this->trans('calculation.result.sorted') => $sorted,
                    $this->trans('calculation.result.updated') => $updated,
                    $this->trans('calculation.result.skipped') => $skipped,
                    $this->trans('calculation.result.unmodifiable') => $unmodifiable,
                    $this->trans('calculation.result.total') => $total,
                ];
                $message = $this->trans('calculation.update.title');
                $logger->info($message, $context);
            }

            // display results
            $data = [
                'empty' => $empty,
                'duplicated' => $duplicated,
                'sorted' => $sorted,
                'updated' => $updated,
                'skipped' => $skipped,
                'unmodifiable' => $unmodifiable,
                'simulated' => $isSimulated,
                'total' => $total,
            ];

            // save values to session
            $this->setSessionValue('admin.update.closed', $includeClosed);
            $this->setSessionValue('admin.update.empty', $includeEmpty);
            $this->setSessionValue('admin.update.duplicated', $includeDuplicated);
            $this->setSessionValue('admin.update.sorted', $includeSorted);
            $this->setSessionValue('admin.update.simulated', $isSimulated);

            return $this->render('calculation/calculation_result.html.twig', $data);
        }

        // display
        return $this->render('calculation/calculation_update.html.twig', [
            'last_update' => $this->getApplication()->getLastUpdate(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * Creates the form helper and add fields for the update calculations.
     */
    private function createUpdateHelper(): FormHelper
    {
        // create form
        $data = [
            'closed' => $this->isSessionBool('admin.update.closed', false),
            'sorted' => $this->isSessionBool('admin.update.sorted', true),
            'empty' => $this->isSessionBool('admin.update.empty', true),
            'duplicated' => $this->isSessionBool('admin.update.duplicated', false),
            'simulated' => $this->isSessionBool('admin.update.simulated', true),
        ];
        $helper = $this->createFormHelper('calculation.update.', $data);

        // fields
        $helper->field('closed')
            ->help('calculation.update.closed_help')
            ->updateHelpAttribute('class', 'ml-4 mb-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('empty')
            ->help('calculation.update.empty_help')
            ->updateHelpAttribute('class', 'ml-4 mb-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('duplicated')
            ->help('calculation.update.duplicated_help')
            ->updateHelpAttribute('class', 'ml-4 mb-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('sorted')
            ->help('calculation.update.sorted_help')
            ->updateHelpAttribute('class', 'ml-4 mb-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('simulated')
            ->help('calculation.update.simulated_help')
            ->updateHelpAttribute('class', 'ml-4 mb-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('confirm')
            ->notMapped()
            ->updateRowAttribute('class', 'mb-0')
            ->updateAttribute('data-error', $this->trans('generate.error.confirm'))
            ->addCheckboxType();

        return $helper;
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

            return  $this->redirectToHomePage();
        }

        // show form
        return $this->render('admin/role_rights.html.twig', [
            'form' => $form->createView(),
            'default' => $default,
        ]);
    }
}
