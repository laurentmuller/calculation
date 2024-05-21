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
use App\Service\CalculationUpdateService;
use App\Tests\DatabaseTrait;
use App\Tests\ServiceTrait;
use App\Tests\Web\AbstractAuthenticateWebTestCase;
use App\Utils\DateUtils;
use App\Utils\FormatUtils;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[CoversClass(CalculationUpdateService::class)]
class CalculationUpdateServiceTest extends AbstractAuthenticateWebTestCase
{
    use DatabaseTrait;
    use ServiceTrait;

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
    }

    /**
     * @throws \Exception
     */
    public function testSaveQuery(): void
    {
        $this->loginUsername(self::ROLE_ADMIN);
        $requestStack = $this->getRequestStack();
        $service = $this->getService(CalculationUpdateService::class);
        $query = $service->createQuery();
        $service->saveQuery($query);

        $session = $requestStack->getSession();
        self::assertSame([], $session->get('calculation.update.states'));

        $expected = $this->getDateFrom();
        $actual = $session->get('calculation.update.date_from');
        self::assertInstanceOf(\DateTimeInterface::class, $actual);
        self::assertSame($expected->getTimestamp(), $actual->getTimestamp());

        $expected = $this->getDateTo();
        /** @psalm-var \DateTimeInterface $actual */
        $actual = $session->get('calculation.update.date_to');
        self::assertInstanceOf(\DateTimeInterface::class, $actual);
        self::assertSame($expected->getTimestamp(), $actual->getTimestamp());
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
    public function testUpdateOne(): void
    {
        $this->loginUsername(self::ROLE_ADMIN);
        $state = new CalculationState();
        $state->setCode('code');
        $this->addEntity($state);

        $date = $this->getDateTo();
        $calculation = new Calculation();
        $calculation->setState($state)
            ->setCustomer('customer')
            ->setDescription('description')
            ->setOverallTotal(100.0)
            ->setDate($date);
        $this->addEntity($calculation);

        $this->loginUsername(self::ROLE_ADMIN);
        $service = $this->getService(CalculationUpdateService::class);
        $query = $service->createQuery();

        $result = $service->update($query);
        self::assertCount(1, $result);
        self::assertCount(1, $result->getResults());
        self::assertTrue($result->isValid());

        $this->deleteEntity($calculation);
        $this->deleteEntity($state);
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

    private function getRequestStack(): RequestStack
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        /** @psalm-var RequestStack $requestStack */
        $requestStack = static::getContainer()->get('request_stack');
        $requestStack->push($request);

        return $requestStack;
    }
}
