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

namespace App\Model;

use App\Entity\CalculationState;
use App\Utils\DateUtils;
use App\Utils\FormatUtils;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Contains parameters to update overall total of calculations.
 */
class CalculationUpdateQuery extends AbstractSimulateQuery
{
    private \DateTimeInterface $dateFrom;

    private \DateTimeInterface $dateTo;

    /** @var CalculationState[] */
    #[Assert\Count(min: 1)]
    private array $states = [];

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->dateFrom = DateUtils::sub(DateUtils::removeTime(), 'P1M');
        $this->dateTo = DateUtils::removeTime();
    }

    public function getDateFrom(): \DateTimeInterface
    {
        return $this->dateFrom;
    }

    public function getDateFromFormatted(): string
    {
        return FormatUtils::formatDate($this->dateFrom);
    }

    public function getDateTo(): \DateTimeInterface
    {
        return $this->dateTo;
    }

    public function getDateToFormatted(): string
    {
        return FormatUtils::formatDate($this->dateTo);
    }

    /**
     * @return CalculationState[]
     */
    public function getStates(): array
    {
        return $this->states;
    }

    public function getStatesCode(): string
    {
        $sources = \array_map(static fn (CalculationState $state): string => (string) $state->getCode(), $this->states);

        return \implode(', ', $sources);
    }

    /**
     * @return int[]
     */
    public function getStatesId(): array
    {
        return \array_map(static fn (CalculationState $state): int => (int) $state->getId(), $this->states);
    }

    public function setDateFrom(\DateTimeInterface $dateFrom): self
    {
        $this->dateFrom = $dateFrom;

        return $this;
    }

    public function setDateTo(\DateTimeInterface $dateTo): self
    {
        $this->dateTo = $dateTo;

        return $this;
    }

    /**
     * @param CalculationState[] $states
     */
    public function setStates(array $states): self
    {
        $this->states = $states;

        return $this;
    }

    /**
     * @throws \Exception
     */
    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        /** @var CalculationUpdateQuery $query */
        $query = $context->getValue();

        // after today?
        $to = $query->getDateTo();
        if ($to > DateUtils::removeTime()) {
            $context->buildViolation('date_less_today')
                ->atPath('dateTo')
                ->addViolation();

            return;
        }

        // after to date
        $from = $query->getDateFrom();
        if ($from > $to) {
            $context->buildViolation('date_less_to')
                ->atPath('dateFrom')
                ->addViolation();

            return;
        }

        // more than 1 month
        $to = DateUtils::sub($to, 'P1M');
        if ($from < $to) {
            $context->buildViolation('date_less_one_month')
                ->atPath('dateFrom')
                ->addViolation();
        }
    }
}
