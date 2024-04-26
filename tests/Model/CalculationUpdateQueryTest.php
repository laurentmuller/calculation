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
use App\Tests\Entity\IdTrait;
use App\Utils\DateUtils;
use App\Utils\FormatUtils;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

#[\PHPUnit\Framework\Attributes\CoversClass(CalculationUpdateQuery::class)]
class CalculationUpdateQueryTest extends TestCase
{
    use IdTrait;

    /**
     * @throws \Exception
     */
    public function testConstruct(): void
    {
        $query = new CalculationUpdateQuery();

        $expected = $this->getDateFrom();
        $actual = $query->getDateFrom();
        self::assertSame($expected->getTimestamp(), $actual->getTimestamp());

        $expected = FormatUtils::formatDate($actual);
        $actual = $query->getDateFromFormatted();
        self::assertSame($expected, $actual);

        $expected = $this->getDateTo();
        $actual = $query->getDateTo();
        self::assertSame($expected->getTimestamp(), $actual->getTimestamp());

        $expected = FormatUtils::formatDate($actual);
        $actual = $query->getDateToFormatted();
        self::assertSame($expected, $actual);
        self::assertTrue($query->isSimulate());
    }

    /**
     * @throws \Exception
     */
    public function testSetDates(): void
    {
        $query = new CalculationUpdateQuery();
        $expected = $this->getDateFrom();
        $query->setDateFrom($expected);
        $actual = $query->getDateFrom();
        self::assertSame($expected->getTimestamp(), $actual->getTimestamp());

        $expected = $this->getDateTo();
        $query->setDateTo($expected);
        $actual = $query->getDateTo();
        self::assertSame($expected->getTimestamp(), $actual->getTimestamp());
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
     * @throws Exception
     * @throws \Exception
     */
    public function testValidateAfter(): void
    {
        $to = DateUtils::removeTime();
        $from = DateUtils::add($to, 'P1M');
        $query = new CalculationUpdateQuery();
        $query->setDateTo($to)
            ->setDateFrom($from);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::once())
            ->method('getValue')
            ->willReturn($query);
        $violation = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();
        $violation->expects(self::any())
            ->method('setParameter')
            ->willReturn($violation);
        $context->expects(self::once())
            ->method('buildViolation')
            ->willReturn($violation);
        $query->validate($context);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testValidateDateTo(): void
    {
        $date = DateUtils::add(DateUtils::removeTime(), 'P1M');
        $query = new CalculationUpdateQuery();
        $query->setDateTo($date);
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::once())
            ->method('getValue')
            ->willReturn($query);
        $violation = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();
        $violation->expects(self::any())
            ->method('setParameter')
            ->willReturn($violation);
        $context->expects(self::once())
            ->method('buildViolation')
            ->willReturn($violation);
        $query->validate($context);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function testValidateMonth(): void
    {
        $to = DateUtils::removeTime();
        $from = DateUtils::sub($to, 'P3M');
        $query = new CalculationUpdateQuery();
        $query->setDateTo($to)
            ->setDateFrom($from);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::once())
            ->method('getValue')
            ->willReturn($query);
        $violation = $this->getMockBuilder(ConstraintViolationBuilderInterface::class)->getMock();
        $violation->expects(self::any())
            ->method('setParameter')
            ->willReturn($violation);
        $context->expects(self::once())
            ->method('buildViolation')
            ->willReturn($violation);
        $query->validate($context);
    }

    /**
     * @throws \ReflectionException
     * @throws Exception
     * @throws \Exception
     */
    public function testValidateValid(): void
    {
        $query = new CalculationUpdateQuery();
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects(self::once())
            ->method('getValue')
            ->willReturn($query);
        $query->validate($context);
        self::assertCount(0, $context->getViolations());
    }

    /**
     * @throws \Exception
     */
    private function getDateFrom(): \DateTimeInterface
    {
        return DateUtils::sub(DateUtils::removeTime(), 'P1M');
    }

    private function getDateTo(): \DateTimeInterface
    {
        return DateUtils::removeTime();
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
