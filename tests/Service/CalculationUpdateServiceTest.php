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

use App\Entity\Category;
use App\Entity\Group;
use App\Entity\GroupMargin;
use App\Entity\Product;
use App\Service\CalculationUpdateService;
use App\Tests\AssertEmptyTrait;
use App\Tests\DatabaseTrait;
use App\Tests\DateAssertTrait;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\Web\AuthenticateWebTestCase;
use App\Utils\DateUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class CalculationUpdateServiceTest extends AuthenticateWebTestCase
{
    use AssertEmptyTrait;
    use CalculationTrait;
    use DatabaseTrait;
    use DateAssertTrait;

    protected function tearDown(): void
    {
        $this->deleteCalculation();
        parent::tearDown();
    }

    public function testCreateQuery(): void
    {
        $service = $this->getService(CalculationUpdateService::class);
        $query = $service->createQuery();
        self::assertEmpty($query->getStates());
        self::assertEmpty($query->getStatesId());
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

    public function testUpdateEmptyState(): void
    {
        $this->loginUsername(self::ROLE_ADMIN);
        $service = $this->getService(CalculationUpdateService::class);
        $query = $service->createQuery();
        $result = $service->update($query);
        self::assertEmpty($result->getResults());
        self::assertEmptyCountable($result);
        self::assertFalse($result->isValid());
    }

    public function testUpdateNoCalculation(): void
    {
        $this->getCalculationState();
        $this->loginUsername(self::ROLE_ADMIN);
        $service = $this->getService(CalculationUpdateService::class);
        $query = $service->createQuery();

        $result = $service->update($query);
        self::assertEmptyCountable($result);
        self::assertEmpty($result->getResults());
        self::assertFalse($result->isValid());
    }

    public function testUpdateOne(): void
    {
        $date = $this->getDate();
        $state = $this->getCalculationState();
        $calculation = $this->getCalculation($state);
        $calculation->setDate($date)
            ->setOverallTotal(100.0);
        $this->addEntity($calculation);

        $this->loginUsername(self::ROLE_ADMIN);
        $session = $this->getRequestStack()->getSession();
        $session->set('calculation.update.states', [$state->getId()]);
        $service = $this->getService(CalculationUpdateService::class);
        $query = $service->createQuery();

        $result = $service->update($query);
        self::assertCount(1, $result);
        self::assertCount(1, $result->getResults());
        self::assertTrue($result->isValid());
    }

    public function testUpdateOneCalculationNoState(): void
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
        self::assertEmptyCountable($result);
        self::assertEmpty($result->getResults());
        self::assertFalse($result->isValid());
    }

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

    public function testUpdateTotalNotEmpty(): void
    {
        $group = new Group();
        $group->setCode('code');
        $this->addEntity($group);
        $groupMargin = new GroupMargin();
        $groupMargin->setMaximum(1000.0)
            ->setMargin(1.0);
        $group->addMargin($groupMargin);

        $category = new Category();
        $category->setCode('code');
        $category->setGroup($group);
        $this->addEntity($category);

        $product = new Product();
        $product->setDescription('Product Test')
            ->setPrice(100.0)
            ->setCategory($category);
        $this->addEntity($product);

        $date = $this->getDate();
        $state = $this->getCalculationState();
        $calculation = $this->getCalculation($state)
            ->addProduct($product)
            ->setDate($date);
        $this->addEntity($calculation);

        $this->loginUsername(self::ROLE_ADMIN);
        $service = $this->getService(CalculationUpdateService::class);
        $query = $service->createQuery();

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
