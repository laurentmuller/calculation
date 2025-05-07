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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Contains parameters to update the overall total of calculations.
 */
class CalculationUpdateQuery extends AbstractSimulateQuery
{
    private \DateTimeImmutable $date;
    private string $interval = 'P1M';
    /** @var CalculationState[] */
    #[Assert\Count(min: 1)]
    private array $states = [];

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->date = DateUtils::removeTime(new \DateTimeImmutable());
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * @throws \Exception
     */
    public function getDateFrom(): \DateTimeImmutable
    {
        return DateUtils::sub($this->date, $this->interval);
    }

    public function getInterval(): string
    {
        return $this->interval;
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
        return \implode(
            ', ',
            $this->mapStates(static fn (CalculationState $state): string => (string) $state->getCode())
        );
    }

    /**
     * @return int[]
     */
    public function getStatesId(): array
    {
        return $this->mapStates(static fn (CalculationState $state): int => (int) $state->getId());
    }

    public function setDate(\DateTimeImmutable $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function setInterval(string $interval): self
    {
        $this->interval = $interval;

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
        // before or equal as today?
        if ($this->date <= DateUtils::removeTime()) {
            return;
        }

        $context->buildViolation('date_less_today')
            ->atPath('date')
            ->addViolation();
    }

    /**
     * @template TResult
     *
     * @phpstan-param callable(CalculationState): TResult $callback
     *
     * @phpstan-return TResult[]
     */
    private function mapStates(callable $callback): array
    {
        return \array_map($callback, $this->states);
    }
}
