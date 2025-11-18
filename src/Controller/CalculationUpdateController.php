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
use App\Model\CalculationUpdateQuery;
use App\Repository\CalculationStateRepository;
use App\Service\CalculationUpdateService;
use App\Utils\DateUtils;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to update the overall total of calculations.
 */
#[Route(path: '/admin', name: 'admin_')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class CalculationUpdateController extends AbstractController
{
    /**
     * @throws \Exception
     */
    #[GetPostRoute(path: '/update', name: 'update')]
    public function update(Request $request, CalculationUpdateService $service): Response
    {
        $query = $service->createQuery();
        $application = $this->getApplicationParameters();
        $form = $this->createQueryForm($query);
        if ($this->handleRequestForm($request, $form)) {
            $service->saveQuery($query);
            $result = $service->update($query);
            if (!$query->isSimulate() && $result->isValid()) {
                $application->getDate()->setUpdateCalculations();
                $application->save();
            }

            return $this->render('admin/update_result.html.twig', [
                'query' => $query,
                'result' => $result,
            ]);
        }

        return $this->render('admin/update_query.html.twig', [
            'last_update' => $application->getDate()->getUpdateCalculations(),
            'form' => $form,
        ]);
    }

    /**
     * @return FormInterface<mixed>
     *
     * @throws \Exception
     */
    private function createQueryForm(CalculationUpdateQuery $query): FormInterface
    {
        $helper = $this->createFormHelper('calculation.update.', $query);
        $helper->field('date')
            ->updateAttribute('max', DateUtils::formatFormDate(DateUtils::createDate()))
            ->addDatePointType();

        $helper->field('interval')
            ->updateOption('choice_translation_domain', false)
            ->addChoiceType($this->getIntervalChoices());

        $helper->field('states')
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

        $helper->addSimulateAndConfirmType($this->getTranslator(), $query->isSimulate());

        return $helper->createForm();
    }

    private function getIntervalChoices(): array
    {
        return [
            $this->trans('counters.weeks', ['count' => 1]) => 'P1W',
            $this->trans('counters.weeks', ['count' => 2]) => 'P2W',
            $this->trans('counters.weeks', ['count' => 3]) => 'P3W',
            $this->trans('counters.months', ['count' => 1]) => 'P1M',
            $this->trans('counters.months', ['count' => 2]) => 'P2M',
            $this->trans('counters.months', ['count' => 3]) => 'P3M',
        ];
    }
}
