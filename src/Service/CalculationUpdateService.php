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

namespace App\Service;

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Form\CalculationState\CalculationStateListType;
use App\Form\FormHelper;
use App\Model\CalculationUpdateQuery;
use App\Model\CalculationUpdateResult;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Traits\LoggerAwareTrait;
use App\Traits\SessionAwareTrait;
use App\Traits\TranslatorAwareTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Service to update overall total of calculations.
 */
class CalculationUpdateService implements ServiceSubscriberInterface
{
    use LoggerAwareTrait;
    use ServiceSubscriberTrait;
    use SessionAwareTrait;
    use TranslatorAwareTrait;

    private const KEY_DATE_FROM = 'calculation.update.date_from';
    private const KEY_DATE_TO = 'calculation.update.date_to';
    private const KEY_SIMULATE = 'calculation.update.simulate';
    private const KEY_STATES = 'calculation.update.states';

    public function __construct(
        private readonly CalculationRepository $calculationRepository,
        private readonly CalculationStateRepository $stateRepository,
        private readonly FormFactoryInterface $factory,
        private readonly SuspendEventListenerService $listenerService,
        private readonly CalculationService $calculationService,
    ) {
    }

    /**
     * Create the edit form.
     *
     * @return FormInterface<mixed>
     */
    public function createForm(CalculationUpdateQuery $query): FormInterface
    {
        $builder = $this->factory->createBuilder(FormType::class, $query);
        $helper = new FormHelper($builder, 'calculation.update.');
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

        $helper->addCheckboxSimulate()
            ->addCheckboxConfirm($this->getTranslator(), $query->isSimulate());

        return $helper->createForm();
    }

    public function createQuery(): CalculationUpdateQuery
    {
        $query = new CalculationUpdateQuery();
        $query->setDateFrom($this->getDateFrom($query))
            ->setDateTo($this->getDateTo($query))
            ->setStates($this->getStates(true))
            ->setSimulate($this->isSimulate());

        return $query;
    }

    public function saveQuery(CalculationUpdateQuery $query): void
    {
        $this->setSessionValues([
            self::KEY_DATE_FROM => $query->getDateFrom(),
            self::KEY_DATE_TO => $query->getDateTo(),
            self::KEY_STATES => $this->getIds($query->getStates()),
            self::KEY_SIMULATE => $query->isSimulate(),
        ]);
    }

    /**
     * @throws ORMException
     */
    public function update(CalculationUpdateQuery $query): CalculationUpdateResult
    {
        $result = new CalculationUpdateResult();
        if ([] === $query->getStates()) {
            return $result;
        }

        /** @psalm-var Calculation[] $calculations */
        $calculations = $this->getCalculations($query);
        if ([] === $calculations) {
            return $result;
        }

        foreach ($calculations as $calculation) {
            $oldTotal = $calculation->getOverallTotal();
            if (!$this->calculationService->updateTotal($calculation)) {
                continue;
            }
            $newTotal = $calculation->getOverallTotal();
            if ($oldTotal === $newTotal) {
                continue;
            }
            $result->addCalculation($oldTotal, $calculation);
        }

        if (!$query->isSimulate() && $result->isValid()) {
            try {
                $this->listenerService->disableListeners();
                $this->calculationRepository->flush();
                // $this->logResult($query, $result);
            } finally {
                $this->listenerService->enableListeners();
            }
        }

        return $result;
    }

    private function getCalculations(CalculationUpdateQuery $query): array
    {
        return $this->calculationRepository
            ->createDefaultQueryBuilder('e')
            ->where('e.state in (:states)')
            ->andWhere('e.date >= :from')
            ->andWhere('e.date <= :to')
            ->setParameter('states', $query->getStates())
            ->setParameter('from', $query->getDateFrom(), Types::DATE_MUTABLE)
            ->setParameter('to', $query->getDateTo(), Types::DATE_MUTABLE)
            ->getQuery()
            ->getResult();
    }

    private function getDateFrom(CalculationUpdateQuery $query): \DateTimeInterface
    {
        return $this->getSessionDate(self::KEY_DATE_FROM, $query->getDateFrom());
    }

    private function getDateTo(CalculationUpdateQuery $query): \DateTimeInterface
    {
        return $this->getSessionDate(self::KEY_DATE_TO, $query->getDateTo());
    }

    /**
     * @param CalculationState[] $sources
     *
     * @return int[]
     */
    private function getIds(array $sources): array
    {
        return \array_map(fn (CalculationState $state): int => (int) $state->getId(), $sources);
    }

    /**
     * @return CalculationState[]
     */
    private function getStates(bool $useSession): array
    {
        /** @var CalculationState[] $sources */
        $sources = $this->stateRepository
            ->getEditableQueryBuilder()
            ->getQuery()
            ->getResult();
        if ($useSession) {
            /** @var int[] $ids */
            $ids = $this->getSessionValue(self::KEY_STATES, []);
            if ([] !== $ids) {
                return \array_filter($sources, fn (CalculationState $state): bool => \in_array($state->getId(), $ids, true));
            }
        }

        return $sources;
    }

    private function isSimulate(): bool
    {
        return $this->isSessionBool(self::KEY_SIMULATE, true);
    }
}
