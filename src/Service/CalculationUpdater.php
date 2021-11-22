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

namespace App\Service;

use App\Entity\Calculation;
use App\Form\FormHelper;
use App\Model\CalculationUpdateQuery;
use App\Model\CalculationUpdateResult;
use App\Repository\CalculationRepository;
use App\Traits\LoggerTrait;
use App\Traits\SessionTrait;
use App\Traits\TranslatorTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to update the calculations.
 *
 * @author Laurent Muller
 */
class CalculationUpdater
{
    use LoggerTrait;
    use SessionTrait;
    use TranslatorTrait;

    private FormFactoryInterface $factory;
    private SuspendEventListenerService $listener;
    private EntityManagerInterface $manager;
    private CalculationService $service;

    /**
     * Constructor.
     */
    public function __construct(
        EntityManagerInterface $manager,
        CalculationService $service,
        SuspendEventListenerService $listener,
        FormFactoryInterface $factory,
        LoggerInterface $logger,
        RequestStack $requestStack,
        TranslatorInterface $translator
    ) {
        $this->manager = $manager;
        $this->service = $service;
        $this->listener = $listener;
        $this->factory = $factory;

        // traits
        $this->logger = $logger;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    /**
     * Creates the edit form.
     */
    public function createForm(CalculationUpdateQuery $query): FormInterface
    {
        // create helper
        $builder = $this->factory->createBuilder(FormType::class, $query);
        $helper = new FormHelper($builder, 'calculation.update.');

        // add fields
        $helper->field('closeCalculations')
            ->help('calculation.update.closeCalculations_help')
            ->helpClass('ml-4 mb-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('emptyCalculations')
            ->help('calculation.update.emptyCalculations_help')
            ->helpClass('ml-4 mb-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('emptyItems')
            ->help('calculation.update.emptyItems_help')
            ->helpClass('ml-4 mb-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('duplicateItems')
            ->help('calculation.update.duplicateItems_help')
            ->helpClass('ml-4 mb-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('copyCodes')
            ->help('calculation.update.copyCodes_help')
            ->helpClass('ml-4 mb-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('sortItems')
            ->help('calculation.update.sortedItems_help')
            ->helpClass('ml-4 mb-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('simulate')
            ->help('calculation.update.simulate_help')
            ->helpClass('ml-4')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('confirm')
            ->updateAttributes(['data-error' => $this->trans('generate.error.confirm'), 'disabled' => $query->isSimulate() ? 'disabled' : null])
            ->notMapped()
            ->addCheckboxType();

        return $helper->createForm();
    }

    /**
     * Create the update query from session.
     */
    public function createUpdateQuery(): CalculationUpdateQuery
    {
        $query = new CalculationUpdateQuery();
        $query->setCloseCalculations($this->isSessionBool('calculation.update.closeCalculations', false))
            ->setEmptyCalculations($this->isSessionBool('calculation.update.emptyCalculations', true))
            ->setEmptyItems($this->isSessionBool('calculation.update.emptyItems', true))
            ->setDuplicateItems($this->isSessionBool('calculation.update.duplicateItems', false))
            ->setSortItems($this->isSessionBool('calculation.update.sortItems', true))
            ->setCopyCodes($this->isSessionBool('calculation.update.copyCodes', true))
            ->setSimulate($this->isSessionBool('calculation.update.simulate', true));

        return $query;
    }

    /**
     * Log the update result.
     */
    public function logResult(CalculationUpdateResult $result): void
    {
        $context = [
            $this->trans('calculation.result.total') => $result->getTotalCalculations(),
            $this->trans('calculation.result.updateCalculations') => $result->getUpdateCalculations(),
            $this->trans('calculation.result.skipCalculations') => $result->getSkipCalculations(),
            $this->trans('calculation.result.unmodifiableCalculations') => $result->getUnmodifiableCalculations(),
            $this->trans('calculation.result.emptyCalculations') => $result->getEmptyCalculations(),

            $this->trans('calculation.result.emptyItems') => $result->getEmptyItems(),
            $this->trans('calculation.result.duplicateItems') => $result->getDuplicateItems(),
            $this->trans('calculation.result.sortItems') => $result->getSortItems(),
            $this->trans('calculation.result.copyCodes') => $result->getCopyCodes(),
        ];
        $message = $this->trans('calculation.update.title');
        $this->logInfo($message, $context);
    }

    /**
     * Save the update query to session.
     */
    public function saveUpdateQuery(CalculationUpdateQuery $query): void
    {
        $this->setSessionValues([
            'calculation.update.closeCalculations' => $query->isCloseCalculations(),
            'calculation.update.emptyCalculations' => $query->isEmptyCalculations(),

            'calculation.update.emptyItems' => $query->isEmptyItems(),
            'calculation.update.sortItems' => $query->isSortItems(),
            'calculation.update.duplicateItems' => $query->isDuplicateItems(),
            'calculation.update.copyCodes' => $query->isCopyCodes(),

            'calculation.update.simulate' => $query->isSimulate(),
        ]);
    }

    /**
     * Update calculations.
     */
    public function update(CalculationUpdateQuery $query): CalculationUpdateResult
    {
        $result = new CalculationUpdateResult();
        $result->setSimulate($query->isSimulate());

        try {
            $this->listener->disableListeners();

            /** @var Calculation[] $calculations */
            $calculations = $this->getCalculations();

            foreach ($calculations as $calculation) {
                if ($query->isCloseCalculations() || $calculation->isEditable()) {
                    $descriptions = [];
                    $changed = false;
                    if ($query->isEmptyCalculations() && $calculation->isEmpty()) {
                        $result->addEmptyCalculations(1);
                        $result->addCalculation($calculation, $this->trans('calculation.update.emptyCalculations'), true);
                        if (!$query->isSimulate()) {
                            $this->manager->remove($calculation);
                        }
                        continue;
                    }
                    if ($query->isEmptyItems() && $calculation->hasEmptyItems()) {
                        $result->addEmptyItems($calculation->removeEmptyItems());
                        $descriptions[] = $this->trans('calculation.update.emptyItems');
                        $changed = true;
                    }

                    if ($query->isCopyCodes() && 0 !== $count = $calculation->updateCodes()) {
                        $result->addCopyCodes($count);
                        $descriptions[] = $this->trans('calculation.update.copyCodes');
                        $changed = true;
                    }

                    if ($query->isDuplicateItems() && $calculation->hasDuplicateItems()) {
                        $result->addDuplicatedItems($calculation->removeDuplicateItems());
                        $descriptions[] = $this->trans('calculation.update.duplicateItems');
                        $changed = true;
                    }

                    if ($query->isSortItems() && $calculation->sort()) {
                        $result->addSortItems(1);
                        $descriptions[] = $this->trans('calculation.update.sortItems');
                        $changed = true;
                    }

                    if ($this->service->updateTotal($calculation)) {
                        $descriptions[] = $this->trans('calculation.update.total');
                        $changed = true;
                    }

                    if ($changed) {
                        $result->addCalculation($calculation, \implode('<br>', $descriptions));
                    }
                } else {
                    $result->addUnmodifiableCalculations(1);
                }
                $result->addTotalCalculations(1);
            }

            if (!$query->isSimulate() && $result->isValid()) {
                // save
                $this->manager->flush();
                $this->logResult($result);
            }
        } finally {
            $this->listener->enableListeners();
        }

        return $result;
    }

    /**
     * Gets all calculations ordered by identifier.
     *
     * @return Calculation[] the calculations
     */
    private function getCalculations(): array
    {
        /** @var CalculationRepository $repository */
        $repository = $this->manager->getRepository(Calculation::class);

        return $repository->findBy([], ['id' => 'ASC']);
    }
}
