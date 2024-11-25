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

namespace App\Tests\Model;

use App\Entity\CalculationState;
use App\Model\CalculationUpdateQuery;
use App\Tests\DateAssertTrait;
use App\Tests\Entity\IdTrait;
use App\Utils\DateUtils;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class CalculationUpdateQueryTest extends TestCase
{
    use DateAssertTrait;
    use IdTrait;

    /**
     * @throws \Exception
     */
    public function testConstruct(): void
    {
        $query = new CalculationUpdateQuery();

        $expected = $this->getDate();
        $actual = $query->getDate();
        self::assertSameDate($expected, $actual);

        self::assertTrue($query->isSimulate());

        $expected = $this->getInterval();
        $actual = $query->getInterval();
        self::assertSame($expected, $actual);

        $expected = $this->getDateFrom();
        $actual = $query->getDateFrom();
        self::assertSameDate($expected, $actual);
    }

    /**
     * @throws \Exception
     */
    public function testSetDates(): void
    {
        $query = new CalculationUpdateQuery();

        $expected = $this->getInterval();
        $query->setInterval($expected);
        self::assertSame($expected, $query->getInterval());

        $expected = $this->getDateFrom();
        $actual = $query->getDateFrom();
        self::assertSameDate($expected, $actual);

        $expected = $this->getDate();
        $query->setDate($expected);
        $actual = $query->getDate();
        self::assertSameDate($expected, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testStates(): void
    {
        $query = new CalculationUpdateQuery();
        self::assertSame([], $query->getStates());
        self::assertSame([], $query->getStatesId());
        self::assertSame('', $query->getStatesCode());

        $state = $this->getState();
        $query->setStates([$state]);

        self::assertSame([$state], $query->getStates());
        self::assertSame([1], $query->getStatesId());
        self::assertSame('code', $query->getStatesCode());
    }

    /**
     * @throws \Exception
     */
    public function testValidationInvalidDate(): void
    {
        $date = DateUtils::add(DateUtils::removeTime(), 'P1D');
        $query = new CalculationUpdateQuery();
        $query->setDate($date);

        $context = $this->getContext(true);
        $query->validate($context);
    }

    /**
     * @throws \Exception
     */
    public function testValidationSuccess(): void
    {
        $query = new CalculationUpdateQuery();
        $context = $this->getContext(false);
        $query->validate($context);
    }

    private function getContext(bool $expectedViolation): MockObject&ExecutionContextInterface
    {
        $context = $this->getMockBuilder(ExecutionContextInterface::class)->getMock();
        if ($expectedViolation) {
            $violation = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)
                ->getMock();
            $violation->expects(self::any())
                ->method('setParameter')
                ->willReturn($violation);
            $violation->expects(self::any())
                ->method('atPath')
                ->willReturn($violation);
            $violation->expects(self::once())
                ->method('addViolation');
            $context->expects(self::once())
                ->method('buildViolation')
                ->willReturn($violation);
        } else {
            $context
                ->expects(self::never())
                ->method('buildViolation');
        }

        return $context;
    }

    private function getDate(): \DateTimeInterface
    {
        return DateUtils::removeTime();
    }

    /**
     * @throws \Exception
     */
    private function getDateFrom(): \DateTimeInterface
    {
        return DateUtils::sub(DateUtils::removeTime(), $this->getInterval());
    }

    private function getInterval(): string
    {
        return 'P1M';
    }

    /**
     * @throws \ReflectionException
     */
    private function getState(): CalculationState
    {
        $state = new CalculationState();
        $state->setCode('code');

        return self::setId($state);
    }
}
