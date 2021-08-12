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
    public function createForm(): FormInterface
    {
        // get values from session
        $data = $this->loadFromSession();

        // create helper
        $builder = $this->factory->createBuilder(FormType::class, $data);
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
            ->updateAttribute('disabled', $data['simulated'] ? 'disabled' : null)
            ->notMapped()
            ->addCheckboxType();

        return $helper->createForm();
    }

    /**
     * Update calculations.
     *
     * @param array $data the form data
     *
     * @return array the result of the update
     */
    public function update(array $data): array
    {
        // get values
        $includeClosed = (bool) $data['closed'];
        $includeSorted = (bool) $data['sorted'];
        $includeEmpty = (bool) $data['empty'];
        $includeDuplicated = (bool) $data['duplicated'];
        $simulated = (bool) $data['simulated'];

        // counters
        $updated = 0;
        $skipped = 0;
        $empty = 0;
        $duplicated = 0;
        $sorted = 0;
        $unmodifiable = 0;
        $total = 0;

        try {
            $this->listener->disableListeners();

            /** @var Calculation[] $calculations */
            $calculations = $this->getCalculations();

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
                    if ($this->service->updateTotal($calculation) || $changed) {
                        ++$updated;
                    } else {
                        ++$skipped;
                    }
                } else {
                    ++$unmodifiable;
                }
            }

            $total = \count($calculations);

            if (!$simulated && $updated > 0) {
                // save
                $this->manager->flush();

                // log results
                $this->logResults($empty, $duplicated, $sorted, $updated, $skipped, $unmodifiable, $total);
            }
        } finally {
            $this->listener->enableListeners();
        }

        // save values to session
        $this->saveToSession($includeClosed, $includeEmpty, $includeDuplicated, $includeSorted, $simulated);

        return [
            'result' => 0 !== $updated,
            'empty' => $empty,
            'duplicated' => $duplicated,
            'sorted' => $sorted,
            'updated' => $updated,
            'skipped' => $skipped,
            'unmodifiable' => $unmodifiable,
            'simulated' => $simulated,
            'total' => $total,
        ];
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

    /**
     * Load user settings from session.
     */
    private function loadFromSession(): array
    {
        return [
            'closed' => $this->isSessionBool('calculation.update.closed', false),
            'sorted' => $this->isSessionBool('calculation.update.sorted', true),
            'empty' => $this->isSessionBool('calculation.update.empty', true),
            'duplicated' => $this->isSessionBool('calculation.update.duplicated', false),
            'simulated' => $this->isSessionBool('calculation.update.simulated', true),
        ];
    }

    /**
     * Log update results.
     */
    private function logResults(int $empty, int $duplicated, int $sorted, int $updated, int $skipped, int $unmodifiable, int $total): void
    {
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
        $this->logInfo($message, $context);
    }

    /**
     * Save user settings to session.
     */
    private function saveToSession(bool $includeClosed, bool $includeEmpty, bool $includeDuplicated, bool $includeSorted, bool $simulated): void
    {
        $this->setSessionValue('calculation.update.closed', $includeClosed);
        $this->setSessionValue('calculation.update.empty', $includeEmpty);
        $this->setSessionValue('calculation.update.duplicated', $includeDuplicated);
        $this->setSessionValue('calculation.update.sorted', $includeSorted);
        $this->setSessionValue('calculation.update.simulated', $simulated);
    }
}