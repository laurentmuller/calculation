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
use App\Model\CalculationArchiveQuery;
use App\Model\CalculationArchiveResult;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Traits\LoggerAwareTrait;
use App\Traits\SessionAwareTrait;
use App\Traits\TranslatorAwareTrait;
use App\Utils\DateUtils;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Service to archive calculations.
 */
class CalculationArchiveService implements ServiceSubscriberInterface
{
    use LoggerAwareTrait;
    use ServiceSubscriberTrait;
    use SessionAwareTrait;
    use TranslatorAwareTrait;

    private const KEY_DATE = 'archive.date';
    private const KEY_SOURCES = 'archive.sources';
    private const KEY_TARGET = 'archive.target';

    public function __construct(
        private readonly CalculationRepository $calculationRepository,
        private readonly CalculationStateRepository $stateRepository,
        private readonly FormFactoryInterface $factory,
        private readonly SuspendEventListenerService $service,
    ) {
    }

    /**
     * Create the edit form.
     *
     * @return FormInterface<mixed>
     */
    public function createForm(CalculationArchiveQuery $query): FormInterface
    {
        $builder = $this->factory->createBuilder(FormType::class, $query);
        $helper = new FormHelper($builder, 'archive.fields.');
        $sources = $this->getSources(false);

        $helper->field('date')
            ->updateAttributes([
                'min' => $this->getDateMinConstraint($sources),
                'max' => $this->getDateMaxConstraint($sources),
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

        $helper->addCheckboxSimulate()
            ->addCheckboxConfirm($this->getTranslator(), $query->isSimulate());

        return $helper->createForm();
    }

    /**
     * Create the archive query.
     */
    public function createQuery(): CalculationArchiveQuery
    {
        $query = new CalculationArchiveQuery();
        $query->setSources($this->getSources(true))
            ->setTarget($this->getTarget())
            ->setDate($this->getDate());

        return $query;
    }

    /**
     * Save the query values to the session.
     */
    public function saveQuery(CalculationArchiveQuery $query): void
    {
        $date = $query->isSimulate() ? $query->getDate()->getTimestamp() : null;
        $this->setSessionValues([
            self::KEY_SOURCES => $query->getSourcesId(),
            self::KEY_TARGET => $query->getTargetId(),
            self::KEY_DATE => $date,
        ]);
    }

    /**
     * Update the calculations.
     */
    public function update(CalculationArchiveQuery $query): CalculationArchiveResult
    {
        $date = $query->getDate();
        $target = $query->getTarget();
        $simulate = $query->isSimulate();
        $sources = $query->getSources();

        $result = new CalculationArchiveResult();
        $calculations = $this->getCalculations($date, $sources);
        foreach ($calculations as $calculation) {
            $oldState = $calculation->getState();
            if ($oldState instanceof CalculationState && $oldState !== $target) {
                $result->addCalculation($oldState, $calculation);
                $calculation->setState($target);
            }
        }
        if (!$simulate && $result->isValid()) {
            try {
                $this->service->disableListeners();
                $this->calculationRepository->flush();
                $this->logResult($query, $result);
            } finally {
                $this->service->enableListeners();
            }
        }

        return $result;
    }

    /**
     * @param CalculationState[] $sources
     */
    private function createQueryBuilder(array $sources, \DateTimeInterface $date = null): QueryBuilder
    {
        $builder = $this->calculationRepository
            ->createQueryBuilder('c');
        if ([] !== $sources) {
            $builder->andWhere('c.state IN (:states)')
                ->setParameter('states', $sources);
        }
        if ($date instanceof \DateTimeInterface) {
            $builder->andWhere('c.date <= :date')
                ->setParameter('date', $date, Types::DATE_MUTABLE);
        }

        return $builder;
    }

    /**
     * Gets the calculations to archive.
     *
     * @param CalculationState[] $sources
     *
     * @return Calculation[]
     */
    private function getCalculations(\DateTimeInterface $date, array $sources): array
    {
        if ([] === $sources) {
            return [];
        }

        /** @psalm-var \Doctrine\ORM\Query<int, Calculation> $query */
        $query = $this->createQueryBuilder($sources, $date)
            ->getQuery();

        return $query->getResult();
    }

    private function getDate(): \DateTimeInterface
    {
        $date = $this->getSessionDate(self::KEY_DATE);
        if ($date instanceof \DateTimeInterface) {
            return $date;
        }
        $sources = $this->getSources(false);
        $minDate = $this->getDateMin($sources);
        if (!$minDate instanceof \DateTime) {
            return (new \DateTime())->sub(new \DateInterval('P6M'));
        }
        $interval = new \DateInterval('P1M');
        $minDate->add($interval);
        $maxDate = $this->getDateMax($sources);
        if ($maxDate instanceof \DateTime && $minDate >= $maxDate) {
            return $maxDate->sub($interval);
        }

        return $minDate;
    }

    /**
     * @param CalculationState[] $sources
     */
    private function getDateMax(array $sources): ?\DateTime
    {
        $builder = $this->createQueryBuilder($sources)
            ->select('MAX(c.date)');

        try {
            /** @var string|null $date */
            $date = $builder->getQuery()->getSingleScalarResult();
            if (null !== $date) {
                return new \DateTime($date);
            }
        } catch (\Exception) {
        }

        return null;
    }

    /**
     * @param CalculationState[] $sources
     */
    private function getDateMaxConstraint(array $sources): ?string
    {
        $date = $this->getDateMax($sources);

        return DateUtils::formatFormDate($date?->sub(new \DateInterval('P1M')));
    }

    /**
     * @param CalculationState[] $sources
     */
    private function getDateMin(array $sources): ?\DateTime
    {
        $builder = $this->createQueryBuilder($sources)
            ->select('MIN(c.date)');

        try {
            /** @var string|null $date */
            $date = $builder->getQuery()->getSingleScalarResult();
            if (null !== $date) {
                return new \DateTime($date);
            }
        } catch (\Exception) {
        }

        return null;
    }

    /**
     * @param CalculationState[] $sources
     */
    private function getDateMinConstraint(array $sources): ?string
    {
        $date = $this->getDateMin($sources);

        return DateUtils::formatFormDate($date);
    }

    /**
     * @return CalculationState[]
     */
    private function getSources(bool $useSession): array
    {
        /** @var CalculationState[] $sources */
        $sources = $this->stateRepository
            ->getEditableQueryBuilder()
            ->getQuery()
            ->getResult();
        if ($useSession) {
            /** @var int[] $ids */
            $ids = $this->getSessionValue(self::KEY_SOURCES, []);
            if ([] !== $ids) {
                return \array_filter($sources, fn (CalculationState $state): bool => \in_array($state->getId(), $ids, true));
            }
        }

        return $sources;
    }

    private function getTarget(): ?CalculationState
    {
        $id = $this->getSessionInt(self::KEY_TARGET, 0);
        if (0 !== $id) {
            return $this->stateRepository->find($id);
        }

        return null;
    }

    private function logResult(CalculationArchiveQuery $query, CalculationArchiveResult $result): void
    {
        $context = [
            $this->trans('archive.fields.date') => $query->getDateFormatted(),
            $this->trans('archive.fields.sources') => $query->getSourcesCode(),
            $this->trans('archive.fields.target') => $query->getTargetCode(),
            $this->trans('archive.result.calculations') => $result->getTotal(),
        ];
        $message = $this->trans('archive.title');
        $this->logInfo($message, $context);
    }
}