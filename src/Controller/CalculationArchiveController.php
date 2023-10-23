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

use App\Form\CalculationState\CalculationStateListType;
use App\Interfaces\RoleInterface;
use App\Model\CalculationArchiveQuery;
use App\Repository\CalculationStateRepository;
use App\Service\CalculationArchiveService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to archive calculations.
 */
#[AsController]
#[Route(path: '/admin')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class CalculationArchiveController extends AbstractController
{
    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Route(path: '/archive', name: 'admin_archive')]
    public function invoke(Request $request, CalculationArchiveService $service): Response
    {
        if (!$service->isEditableStates()) {
            return $this->redirectToHomePage('archive.editable_empty');
        }
        if (!$service->isNotEditableStates()) {
            return $this->redirectToHomePage('archive.not_editable_empty');
        }

        $query = $service->createQuery();
        $application = $this->getApplication();
        $form = $this->createQueryForm($service, $query);
        if ($this->handleRequestForm($request, $form)) {
            $service->saveQuery($query);
            $result = $service->update($query);
            if (!$query->isSimulate() && $result->isValid()) {
                $application->setLastArchiveCalculations();
            }

            return $this->render('admin/archive_result.html.twig', [
                'query' => $query,
                'result' => $result,
            ]);
        }

        return $this->render('admin/archive_query.html.twig', [
            'last_update' => $application->getLastArchiveCalculations(),
            'form' => $form,
        ]);
    }

    private function createQueryForm(CalculationArchiveService $service, CalculationArchiveQuery $query): FormInterface
    {
        $helper = $this->createFormHelper('archive.fields.', $query);
        $helper->field('date')
            ->updateAttributes([
                'min' => $service->getDateMinConstraint(),
                'max' => $service->getDateMaxConstraint(),
            ])
            ->addDateType();
        $helper->field('sources')
            ->updateOptions([
                'multiple' => true,
                'expanded' => true,
                'group_by' => fn () => null,
                'query_builder' => static fn (CalculationStateRepository $repository): QueryBuilder => $repository->getEditableQueryBuilder(),
            ])
            ->labelClass('checkbox-inline checkbox-switch')
            ->add(CalculationStateListType::class);
        $helper->field('target')
            ->updateOptions([
                'group_by' => fn () => null,
                'query_builder' => static fn (CalculationStateRepository $repository): QueryBuilder => $repository->getNotEditableQueryBuilder(),
            ])
            ->add(CalculationStateListType::class);
        $helper->addSimulateAndConfirmType($this->getTranslator(), $query->isSimulate());

        return $helper->createForm();
    }
}
