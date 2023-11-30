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
use App\Model\CalculationUpdateQuery;
use App\Repository\CalculationStateRepository;
use App\Service\CalculationUpdateService;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to update overall total of calculations.
 */
#[AsController]
#[Route(path: '/admin')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class CalculationUpdateController extends AbstractController
{
    /**
     * @throws ORMException
     */
    #[Route(path: '/update', name: 'admin_update', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function update(Request $request, CalculationUpdateService $service): Response
    {
        $query = $service->createQuery();
        $application = $this->getApplication();
        $form = $this->createQueryForm($query);
        if ($this->handleRequestForm($request, $form)) {
            $service->saveQuery($query);
            $result = $service->update($query);
            if (!$query->isSimulate() && $result->isValid()) {
                $application->setLastUpdateCalculations();
            }

            return $this->render('admin/update_result.html.twig', [
                'query' => $query,
                'result' => $result,
            ]);
        }

        return $this->render('admin/update_query.html.twig', [
            'last_update' => $application->getLastUpdateCalculations(),
            'form' => $form,
        ]);
    }

    private function createQueryForm(CalculationUpdateQuery $query): FormInterface
    {
        $helper = $this->createFormHelper('calculation.update.', $query);

        $helper->field('dateFrom')
            ->addDateType();

        $helper->field('dateTo')
            ->addDateType();

        $helper->field('states')
            ->updateOptions([
                'multiple' => true,
                'expanded' => true,
                'group_by' => fn () => null,
                'query_builder' => static fn (CalculationStateRepository $repository): QueryBuilder => $repository->getEditableQueryBuilder(),
            ])
            ->labelClass('checkbox-inline checkbox-switch')
            ->add(CalculationStateListType::class);

        $helper->addSimulateAndConfirmType($this->getTranslator(), $query->isSimulate());

        return $helper->createForm();
    }
}
