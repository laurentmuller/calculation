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
use App\Entity\CalculationState;
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

    private const KEY_CLOSE_CALCULATIONS = 'calculation.update.closeCalculations';
    private const KEY_COPY_CODE = 'calculation.update.copyCodes';
    private const KEY_DUPLICATE_ITEMS = 'calculation.update.duplicateItems';
    private const KEY_EMPTY_CALCULATIONS = 'calculation.update.emptyCalculations';
    private const KEY_EMPTY_ITEMS = 'calculation.update.emptyItems';
    private const KEY_SIMULATE = 'calculation.update.simulated';
    private const KEY_SORT_ITEMS = 'calculation.update.sortItems';

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
            ->helpParameters(['%codes%' => $this->getNonEditableCodes()])
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
            ->help('calculation.update.sortItems_help')
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
        $query->setCloseCalculations($this->isSessionBool(self::KEY_CLOSE_CALCULATIONS, false))
            ->setEmptyCalculations($this->isSessionBool(self::KEY_EMPTY_CALCULATIONS, true))
            ->setEmptyItems($this->isSessionBool(self::KEY_EMPTY_ITEMS, true))
            ->setDuplicateItems($this->isSessionBool(self::KEY_DUPLICATE_ITEMS, false))
            ->setSortItems($this->isSessionBool(self::KEY_SORT_ITEMS, true))
            ->setCopyCodes($this->isSessionBool(self::KEY_COPY_CODE, true))
            ->setSimulate($this->isSessionBool(self::KEY_SIMULATE, true));

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
            self::KEY_CLOSE_CALCULATIONS => $query->isCloseCalculations(),
            self::KEY_EMPTY_CALCULATIONS => $query->isEmptyCalculations(),

            self::KEY_EMPTY_ITEMS => $query->isEmptyItems(),
            self::KEY_SORT_ITEMS => $query->isSortItems(),
            self::KEY_DUPLICATE_ITEMS => $query->isDuplicateItems(),
            self::KEY_COPY_CODE => $query->isCopyCodes(),

            self::KEY_SIMULATE => $query->isSimulate(),
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
                    if ($query->isEmptyCalculations() && $calculation->isEmpty()) {
                        $result->addEmptyCalculations(1);
                        $result->addCalculation($calculation, $this->trans('calculation.update.emptyCalculations'), true);
                        if (!$query->isSimulate()) {
                            $this->manager->remove($calculation);
                        }
                        continue;
                    }
                    $messages = [];
                    $changed = false;
                    if ($query->isEmptyItems() && $calculation->hasEmptyItems()) {
                        $result->addEmptyItems($calculation->removeEmptyItems());
                        $messages[] = $this->trans('calculation.update.emptyItems');
                        $changed = true;
                    }

                    if ($query->isCopyCodes() && 0 !== $count = $calculation->updateCodes()) {
                        $result->addCopyCodes($count);
                        $messages[] = $this->trans('calculation.update.copyCodes');
                        $changed = true;
                    }

                    if ($query->isDuplicateItems() && $calculation->hasDuplicateItems()) {
                        $result->addDuplicatedItems($calculation->removeDuplicateItems());
                        $messages[] = $this->trans('calculation.update.duplicateItems');
                        $changed = true;
                    }

                    if ($query->isSortItems() && $calculation->sort()) {
                        $result->addSortItems(1);
                        $messages[] = $this->trans('calculation.update.sortItems');
                        $changed = true;
                    }

                    if ($this->service->updateTotal($calculation)) {
                        $messages[] = $this->trans('calculation.update.total');
                        $changed = true;
                    }

                    if ($changed) {
                        $result->addCalculation($calculation, $messages);
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

    /**
     * Gets merged codes of non-editable calculation states.
     */
    private function getNonEditableCodes(): string
    {
        $repository = $this->manager->getRepository(CalculationState::class);
        $codes = $repository->createQueryBuilder('e')
            ->select('e.code')
            ->orderBy('e.code')
            ->where('e.editable = false')
            ->getQuery()
            ->getSingleColumnResult();

        return \implode(', ', $codes);
    }
}
