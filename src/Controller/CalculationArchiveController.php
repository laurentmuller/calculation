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
use App\Form\CalculationState\CalculationStateListType;
use App\Interfaces\RoleInterface;
use App\Model\CalculationArchiveQuery;
use App\Repository\CalculationStateRepository;
use App\Service\CalculationArchiveService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to archive calculations.
 */
#[Route(path: '/admin', name: 'admin_')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class CalculationArchiveController extends AbstractController
{
    #[GetPostRoute(path: '/archive', name: 'archive')]
    public function invoke(Request $request, CalculationArchiveService $service): Response
    {
        if (!$service->isEditableStates()) {
            return $this->redirectToHomePage('archive.editable_empty', request: $request);
        }
        if (!$service->isNotEditableStates()) {
            return $this->redirectToHomePage('archive.not_editable_empty', request: $request);
        }

        $query = $service->createQuery();
        $application = $this->getApplicationParameters();
        $datesParameter = $application->getDates();

        $form = $this->createQueryForm($service, $query);
        if ($this->handleRequestForm($request, $form)) {
            $service->saveQuery($query);
            $result = $service->update($query);
            if (!$query->isSimulate() && $result->isValid()) {
                $datesParameter->setArchiveCalculations();
                $application->save();
            }

            return $this->render('admin/archive_result.html.twig', [
                'query' => $query,
                'result' => $result,
            ]);
        }

        return $this->render('admin/archive_query.html.twig', [
            'last_update' => $datesParameter->getArchiveCalculations(),
            'form' => $form,
        ]);
    }

    /**
     * @return FormInterface<mixed>
     */
    private function createQueryForm(CalculationArchiveService $service, CalculationArchiveQuery $query): FormInterface
    {
        $helper = $this->createFormHelper('archive.fields.', $query);
        $helper->field('date')
            ->updateAttributes([
                'min' => $service->getDateMinConstraint(),
                'max' => $service->getDateMaxConstraint(),
            ])
            ->addDatePointType();

        $helper->field('sources')
            ->updateOptions([
                'multiple' => true,
                'expanded' => true,
                'group_by' => static fn (): null => null,
                'query_builder' => static fn (
                    CalculationStateRepository $repository
                ): QueryBuilder => $repository->getEditableQueryBuilder(),
            ])
            ->labelClass('checkbox-inline checkbox-switch')
            ->add(CalculationStateListType::class);

        $helper->field('target')
            ->updateOptions([
                'group_by' => static fn (): null => null,
                'query_builder' => static fn (
                    CalculationStateRepository $repository
                ): QueryBuilder => $repository->getNotEditableQueryBuilder(),
            ])
            ->add(CalculationStateListType::class);

        $helper->addSimulateAndConfirmType($this->getTranslator(), $query->isSimulate());

        return $helper->createForm();
    }
}
