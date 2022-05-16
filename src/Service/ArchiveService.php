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
use App\Model\ArchiveQuery;
use App\Model\ArchiveResult;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Traits\TranslatorTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contains information about archive calculations.
 */
class ArchiveService
{
    use TranslatorTrait;

    /**
     * Constructor.
     */
    public function __construct(private readonly CalculationRepository $calculationRepository, private readonly CalculationStateRepository $stateRepository, private readonly FormFactoryInterface $factory, TranslatorInterface $translator)
    {
        $this->setTranslator($translator);
    }

    /**
     * Create the edit form.
     */
    public function createForm(ArchiveQuery $query): FormInterface
    {
        // create helper
        $builder = $this->factory->createBuilder(FormType::class, $query);
        $helper = new FormHelper($builder, 'archive.fields.');

        // add fields
        $helper->field('date')
            ->addDateType();

        $helper->field('sources')
            ->updateOption('multiple', true)
            ->updateOption('expanded', true)
            ->updateOption('group_by', null)
            ->updateOption('use_group_by', false)
            ->labelClass('switch-custom')
            ->widgetClass('form-check form-check-inline')
            ->updateOption('query_builder', fn (CalculationStateRepository $repository): QueryBuilder => $repository->getEditableQueryBuilder())
            ->add(CalculationStateListType::class);

        $helper->field('target')
            ->updateOption('use_group_by', false)
            ->updateOption('query_builder', fn (CalculationStateRepository $repository): QueryBuilder => $repository->getNotEditableQueryBuilder())
            ->add(CalculationStateListType::class);

        $helper->field('simulate')
            ->label('product.update.simulate')
            ->help('product.update.simulate_help')
            ->helpClass('ml-4')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('confirm')
            ->label('product.update.confirm')
            ->updateAttribute('data-error', $this->trans('generate.error.confirm'))
            ->updateAttribute('disabled', $query->isSimulate() ? 'disabled' : null)
            ->notMapped()
            ->addCheckboxType();

        return $helper->createForm();
    }

    /**
     * Create the archive query.
     */
    public function createQuery(): ArchiveQuery
    {
        $query = new ArchiveQuery();
        $query->setDate($this->getDate());
        $query->setSources($this->getSources());

        return $query;
    }

    /**
     * Process the archive query and return result.
     */
    public function processQuery(ArchiveQuery $query): ArchiveResult
    {
        $result = new ArchiveResult($query->getDate(), $query->getTarget(), $query->isSimulate());

        $calculations = $this->getCalculations($query);
        foreach ($calculations as $calculation) {
            $state = $calculation->getState();
            if (null !== $state) {
                $result->addCalculation($state, $calculation);
            }
        }

        return $result;
    }

    /**
     * Gets the calculations to archive.
     *
     * @return Calculation[]
     */
    private function getCalculations(ArchiveQuery $query): array
    {
        $sources = $query->getSources();
        if (empty($sources)) {
            return [];
        }

        $ids = \array_map(fn (CalculationState $state): int => (int) $state->getId(), $sources);
        $queryBuilder = $this->calculationRepository->createQueryBuilder('c')
            ->where('c.date <= :date')
            ->andWhere('c.state IN (:states)')
            ->setParameter('date', $query->getDate(), Types::DATE_MUTABLE)
            ->setParameter('states', $ids)
            ->getQuery();

        /** @var Calculation[] $result */
        $result = $queryBuilder->getResult();

        return $result;
    }

    private function getDate(): \DateTimeInterface
    {
        $dt = new \DateInterval('P6M');

        return (new \DateTime())->sub($dt);
    }

    /**
     * @return CalculationState[]
     */
    private function getSources(): array
    {
        /** @var CalculationState[] $sources */
        $sources = $this->stateRepository->getEditableQueryBuilder()->getQuery()->getResult();

        return $sources;
    }
}
