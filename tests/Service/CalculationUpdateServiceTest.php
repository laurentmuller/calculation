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

use App\Service\CalculationUpdateService;
use App\Tests\DatabaseTrait;
use App\Tests\DateAssertTrait;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\Web\AuthenticateWebTestCase;
use App\Utils\DateUtils;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class CalculationUpdateServiceTest extends AuthenticateWebTestCase
{
    use CalculationTrait;
    use DatabaseTrait;
    use DateAssertTrait;

    /**
     * @throws ORMException
     */
    protected function tearDown(): void
    {
        $this->deleteCalculation();
        parent::tearDown();
    }

    /**
     * @throws \Exception
     */
    public function testCreateQuery(): void
    {
        $service = $this->getService(CalculationUpdateService::class);
        $query = $service->createQuery();
        self::assertCount(0, $query->getStates());
        self::assertCount(0, $query->getStatesId());
        self::assertSame('', $query->getStatesCode());

        $expected = $this->getDateFrom();
        $actual = $query->getDateFrom();
        self::assertSameDate($expected, $actual);

        $expected = $this->getDate();
        $actual = $query->getDate();
        self::assertSameDate($expected, $actual);

        $expected = $this->getInterval();
        $actual = $query->getInterval();
        self::assertSame($expected, $actual);
    }

    /**
     * @throws \Exception
     */
    public function testSaveQuery(): void
    {
        $this->loginUsername(self::ROLE_ADMIN);
        $requestStack = $this->getRequestStack();
        $session = $requestStack->getSession();

        $service = $this->getService(CalculationUpdateService::class);
        $query = $service->createQuery();
        $service->saveQuery($query);

        $expected = [];
        $actual = $session->get('calculation.update.states');
        self::assertIsArray($actual);
        self::assertSame($expected, $actual);

        $expected = $this->getDate();
        /** @psalm-var mixed $actual */
        $actual = $session->get('calculation.update.date');
        self::assertInstanceOf(\DateTimeInterface::class, $actual);
        self::assertSameDate($expected, $actual);

        $expected = $this->getInterval();
        /** @psalm-var mixed $actual */
        $actual = $session->get('calculation.update.interval');
        self::assertIsString($actual);
        self::assertSame($expected, $actual);
    }

    /**
     * @throws ORMException
     */
    public function testUpdateEmpty(): void
    {
        $this->loginUsername(self::ROLE_ADMIN);
        $service = $this->getService(CalculationUpdateService::class);
        $query = $service->createQuery();
        $result = $service->update($query);
        self::assertCount(0, $result->getResults());
        self::assertCount(0, $result);
        self::assertFalse($result->isValid());
    }

    /**
     * @throws ORMException
     */
    public function testUpdateNoCalculation(): void
    {
        $date = $this->getDate();
        $state = $this->getCalculationState();
        $calculation = $this->getCalculation($state);
        $calculation->setDate($date)
            ->setOverallTotal(0.0);
        $this->addEntity($calculation);

        $this->loginUsername(self::ROLE_ADMIN);
        $service = $this->getService(CalculationUpdateService::class);
        $query = $service->createQuery();

        $result = $service->update($query);
        self::assertCount(0, $result);
        self::assertCount(0, $result->getResults());
        self::assertFalse($result->isValid());
    }

    /**
     * @throws ORMException
     */
    public function testUpdateOne(): void
    {
        $date = $this->getDate();
        $state = $this->getCalculationState();
        $calculation = $this->getCalculation($state);
        $calculation->setDate($date)
            ->setOverallTotal(100.0);
        $this->addEntity($calculation);

        $this->loginUsername(self::ROLE_ADMIN);
        $service = $this->getService(CalculationUpdateService::class);
        $query = $service->createQuery();

        $result = $service->update($query);
        self::assertCount(1, $result);
        self::assertCount(1, $result->getResults());
        self::assertTrue($result->isValid());
    }

    /**
     * @throws ORMException
     */
    public function testUpdateOneNoSimulate(): void
    {
        $date = $this->getDate();
        $state = $this->getCalculationState();
        $calculation = $this->getCalculation($state);
        $calculation->setDate($date)
            ->setOverallTotal(100.0);
        $this->addEntity($calculation);

        $this->loginUsername(self::ROLE_ADMIN);
        $service = $this->getService(CalculationUpdateService::class);
        $query = $service->createQuery();
        $query->setSimulate(false);

        $result = $service->update($query);
        self::assertCount(1, $result);
        self::assertCount(1, $result->getResults());
        self::assertTrue($result->isValid());
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

    private function getRequestStack(): RequestStack
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        $requestStack = $this->getService(RequestStack::class);
        $requestStack->push($request);

        return $requestStack;
    }
}
