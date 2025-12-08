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

namespace App\Tests\Service;

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Model\CalculationArchiveQuery;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Service\CalculationArchiveService;
use App\Service\SuspendEventListenerService;
use App\Tests\DateAssertTrait;
use App\Tests\Entity\IdTrait;
use App\Tests\TranslatorMockTrait;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class CalculationArchiveServiceTest extends TestCase
{
    use DateAssertTrait;
    use IdTrait;
    use TranslatorMockTrait;

    private MockObject&CalculationRepository $calculationRepository;
    private Session $session;
    private MockObject&CalculationStateRepository $stateRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->session = new Session(new MockArraySessionStorage());
        $this->calculationRepository = $this->createMock(CalculationRepository::class);
        $this->stateRepository = $this->createMock(CalculationStateRepository::class);
    }

    public function testCreateQueryEmpty(): void
    {
        $this->setCalculationStates();
        $service = $this->createService();
        $actual = $service->createQuery();
        self::assertEmpty($actual->getSources());
        self::assertEmpty($actual->getSourcesId());
        self::assertNull($actual->getTarget());
        self::assertInstanceOf(DatePoint::class, $actual->getDate());
    }

    public function testCreateQueryWithDate(): void
    {
        $expected = new DatePoint('2024-02-01');
        $stateEditable = new CalculationState();
        $stateEditable->setCode('editable')
            ->setEditable(true);
        $this->setCalculationStates([$stateEditable]);
        $this->setCalculationDate('2024-03-01');
        $service = $this->createService();
        $actual = $service->createQuery();
        self::assertTimestampEquals($expected, $actual->getDate());
    }

    public function testCreateQueryWithSessionDate(): void
    {
        $expected = new DatePoint('2024-01-01');
        $this->session->set('archive.date', $expected);
        $this->setCalculationStates();
        $service = $this->createService();
        $query = $service->createQuery();
        $actual = $query->getDate();
        self::assertDateEquals($expected, $actual);
    }

    public function testCreateQueryWithSessionSources(): void
    {
        $expected = new CalculationState();
        $expected->setCode('not_editable')
            ->setEditable(false);
        self::setId($expected);
        $this->setCalculationStates([$expected]);

        $this->session->set('archive.sources', [1]);
        $this->setCalculationStates();
        $service = $this->createService();
        $actual = $service->createQuery();
        self::assertCount(1, $actual->getSources());
        self::assertSame($expected, $actual->getSources()[0]);
    }

    public function testCreateQueryWithSessionTarget(): void
    {
        $expected = new CalculationState();
        $expected->setCode('not_editable')
            ->setEditable(false);
        self::setId($expected);
        $this->setCalculationStates([$expected]);

        $this->stateRepository->method('find')
            ->willReturn($expected);

        $this->session->set('archive.target', 1);
        $this->setCalculationStates();
        $service = $this->createService();
        $actual = $service->createQuery();
        self::assertNotNull($actual->getTarget());
        self::assertSame($expected, $actual->getTarget());
    }

    public function testCreateQueryWithSources(): void
    {
        $stateEditable = new CalculationState();
        $stateEditable->setCode('editable')
            ->setEditable(true);
        $this->setCalculationStates([$stateEditable]);

        $service = $this->createService();
        $actual = $service->createQuery();
        self::assertCount(1, $actual->getSources());
        self::assertSame([$stateEditable], $actual->getSources());
        self::assertInstanceOf(DatePoint::class, $actual->getDate());
    }

    public function testGetDateMaxConstraintDate(): void
    {
        $expected = '2024-01-01';
        $this->setCalculationDate('2024-02-01');
        $this->setCalculationStates();
        $service = $this->createService();
        $actual = $service->getDateMaxConstraint();
        self::assertNotNull($actual);
        self::assertSame($expected, $actual);
    }

    public function testGetDateMaxConstraintException(): void
    {
        $this->setCalculationStates();
        $service = $this->createService();
        $actual = $service->getDateMaxConstraint();
        self::assertNull($actual);
    }

    public function testGetDateMaxConstraintNull(): void
    {
        $this->setCalculationDate();
        $this->setCalculationStates();
        $service = $this->createService();
        $actual = $service->getDateMaxConstraint();
        self::assertNull($actual);
    }

    public function testGetDateMinConstraintDate(): void
    {
        $expected = '2024-01-01';
        $this->setCalculationDate($expected);
        $this->setCalculationStates();
        $service = $this->createService();
        $actual = $service->getDateMinConstraint();
        self::assertNotNull($actual);
        self::assertSame($expected, $actual);
    }

    public function testGetDateMinConstraintException(): void
    {
        $this->setCalculationStates();
        $service = $this->createService();
        $actual = $service->getDateMinConstraint();
        self::assertNull($actual);
    }

    public function testGetDateMinConstraintNull(): void
    {
        $this->setCalculationStates();
        $service = $this->createService();
        $actual = $service->getDateMinConstraint();
        self::assertNull($actual);
    }

    public function testIsEditableCount(): void
    {
        $this->stateRepository->method('getEditableCount')
            ->willReturn(0);
        $service = $this->createService();
        $actual = $service->isEditableStates();
        self::assertFalse($actual);
    }

    public function testIsNotEditableCount(): void
    {
        $this->stateRepository->method('getNotEditableCount')
            ->willReturn(0);
        $service = $this->createService();
        $actual = $service->isNotEditableStates();
        self::assertFalse($actual);
    }

    public function testSaveQuery(): void
    {
        $query = new CalculationArchiveQuery();
        $service = $this->createService();
        $service->saveQuery($query);
        self::assertSame([], $this->session->get('archive.sources'));
        self::assertNull($this->session->get('archive.target'));
        self::assertNotNull($this->session->get('archive.date'));
    }

    public function testUpdateNull(): void
    {
        $query = new CalculationArchiveQuery();
        $service = $this->createService();
        $actual = $service->update($query);
        self::assertEmpty($actual->getResults());
    }

    public function testUpdateWithNotSimulate(): void
    {
        $stateEditable = new CalculationState();
        $stateEditable->setCode('editable')
            ->setEditable(true);
        $stateNotEditable = new CalculationState();
        $stateNotEditable->setCode('not_editable')
            ->setEditable(false);
        $this->setCalculationStates([$stateEditable, $stateNotEditable]);

        $calculation = new Calculation();
        $calculation->setState($stateEditable);
        $this->setCalculations([$calculation]);

        $query = new CalculationArchiveQuery();
        $query->setSources([$stateEditable]);
        $query->setTarget($stateNotEditable);
        $query->setSimulate(false);

        $service = $this->createService();
        $actual = $service->update($query);
        self::assertCount(1, $actual);
    }

    public function testUpdateWithSimulate(): void
    {
        $stateEditable = new CalculationState();
        $stateEditable->setCode('editable')
            ->setEditable(true);
        $stateNotEditable = new CalculationState();
        $stateNotEditable->setCode('not_editable')
            ->setEditable(false);
        $this->setCalculationStates([$stateEditable, $stateNotEditable]);

        $calculation = new Calculation();
        $calculation->setState($stateEditable);
        $this->setCalculations([$calculation]);

        $query = new CalculationArchiveQuery();
        $query->setSources([$stateEditable]);
        $query->setTarget($stateNotEditable);

        $service = $this->createService();
        $actual = $service->update($query);
        self::assertCount(1, $actual);
    }

    private function createRequestStack(): MockObject&RequestStack
    {
        $request = new Request();
        $request->setSession($this->session);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')
            ->willReturn($request);
        $requestStack->method('getSession')
            ->willReturn($this->session);

        return $requestStack;
    }

    private function createService(): CalculationArchiveService
    {
        $listenerService = $this->createMock(SuspendEventListenerService::class);
        $service = new CalculationArchiveService(
            $this->calculationRepository,
            $this->stateRepository,
            $listenerService
        );
        $service->setTranslator($this->createMockTranslator());
        $service->setLogger($this->createMock(LoggerInterface::class));
        $service->setRequestStack($this->createRequestStack());

        return $service;
    }

    private function setCalculationDate(?string $value = null): void
    {
        $query = $this->createMock(Query::class);
        $query->method('getSingleScalarResult')
            ->willReturn($value);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getQuery')
            ->willReturn($query);

        $queryBuilder->method('select')
            ->willReturn($queryBuilder);

        $this->calculationRepository->method('createQueryBuilder')
            ->willReturn($queryBuilder);
    }

    private function setCalculations(array $calculations = []): void
    {
        $query = $this->createMock(Query::class);
        $query->method('getResult')
            ->willReturn($calculations);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getQuery')
            ->willReturn($query);

        $queryBuilder->method('select')
            ->willReturn($queryBuilder);

        $this->calculationRepository->method('createQueryBuilder')
            ->willReturn($queryBuilder);
    }

    private function setCalculationStates(array $calculationStates = []): void
    {
        $query = $this->createMock(Query::class);
        $query->method('getResult')
            ->willReturn($calculationStates);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getQuery')
            ->willReturn($query);

        $this->stateRepository->method('getEditableQueryBuilder')
            ->willReturn($queryBuilder);
    }
}
