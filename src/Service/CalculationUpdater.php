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
        $helper->field('closed')
            ->help('calculation.update.closed_help')
            ->helpClass('ml-4 mb-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('empty')
            ->help('calculation.update.empty_help')
            ->helpClass('ml-4 mb-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('duplicated')
            ->help('calculation.update.duplicated_help')
            ->helpClass('ml-4 mb-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('codes')
            ->help('calculation.update.codes_help')
            ->helpClass('ml-4 mb-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('sorted')
            ->help('calculation.update.sorted_help')
            ->helpClass('ml-4 mb-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('simulated')
            ->help('calculation.update.simulated_help')
            ->helpClass('ml-4')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('confirm')
            ->updateAttribute('data-error', $this->trans('generate.error.confirm'))
            ->updateAttribute('disabled', $query->isSimulated() ? 'disabled' : null)
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
        $query->setCodes($this->isSessionBool('calculation.update.codes', true))
            ->setEmpty($this->isSessionBool('calculation.update.empty', true))
            ->setSorted($this->isSessionBool('calculation.update.sorted', true))
            ->setClosed($this->isSessionBool('calculation.update.closed', false))
            ->setSimulated($this->isSessionBool('calculation.update.simulated', true))
            ->setDuplicated($this->isSessionBool('calculation.update.duplicated', false));

        return $query;
    }

    /**
     * Log the update result.
     */
    public function logResult(CalculationUpdateResult $result): void
    {
        $context = [
            $this->trans('calculation.result.total') => $result->getTotal(),
            $this->trans('calculation.result.updated') => $result->getUpdated(),
            $this->trans('calculation.result.skipped') => $result->getSkipped(),
            $this->trans('calculation.result.unmodifiable') => $result->getUnmodifiable(),

            $this->trans('calculation.result.codes') => $result->getCodes(),
            $this->trans('calculation.result.empty') => $result->getEmpty(),
            $this->trans('calculation.result.sorted') => $result->getSorted(),
            $this->trans('calculation.result.duplicated') => $result->getDuplicated(),
        ];
        $message = $this->trans('calculation.update.title');
        $this->logInfo($message, $context);
    }

    /**
     * Save the update query to session.
     */
    public function saveUpdateQuery(CalculationUpdateQuery $query): void
    {
        $this->setSessionValue('calculation.update.codes', $query->isCodes());
        $this->setSessionValue('calculation.update.empty', $query->isEmpty());
        $this->setSessionValue('calculation.update.sorted', $query->isSorted());
        $this->setSessionValue('calculation.update.closed', $query->isClosed());
        $this->setSessionValue('calculation.update.simulated', $query->isSimulated());
        $this->setSessionValue('calculation.update.duplicated', $query->isDuplicated());
    }

    /**
     * Update calculations.
     */
    public function update(CalculationUpdateQuery $query): CalculationUpdateResult
    {
        $result = new CalculationUpdateResult();

        try {
            $this->listener->disableListeners();

            /** @var Calculation[] $calculations */
            $calculations = $this->getCalculations();

            foreach ($calculations as $calculation) {
                if ($query->isClosed() || $calculation->isEditable()) {
                    $changed = false;
                    if ($query->isEmpty() && $calculation->hasEmptyItems()) {
                        $result->addEmpty($calculation->removeEmptyItems());
                        $changed = true;
                    }

                    if ($query->isCodes()) {
                        if (0 !== $count = $calculation->updateCodes()) {
                            $result->addCodes($count);
                            $changed = true;
                        }
                    }

                    if ($query->isDuplicated() && $calculation->hasDuplicateItems()) {
                        $result->addDuplicated($calculation->removeDuplicateItems());
                        $changed = true;
                    }

                    if ($query->isSorted() && $calculation->sort()) {
                        $result->addSorted(1);
                        $changed = true;
                    }

                    if ($this->service->updateTotal($calculation) || $changed) {
                        $result->addUpdated(1);
                    } else {
                        $result->addSkipped(1);
                    }
                } else {
                    $result->addUnmodifiable(1);
                }
                $result->addTotal(1);
            }

            if (!$query->isSimulated() && $result->isValid()) {
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
     * Gets all calculations.
     *
     * @return Calculation[] the calculations
     */
    private function getCalculations(): array
    {
        return $this->manager->getRepository(Calculation::class)->findAll();
    }
}
