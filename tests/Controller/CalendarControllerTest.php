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

namespace App\Tests\Controller;

use App\Entity\Calculation;
use App\Tests\EntityTrait\CalculationTrait;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\HttpFoundation\Response;

class CalendarControllerTest extends ControllerTestCase
{
    use CalculationTrait;

    public static function getRoutes(): \Iterator
    {
        yield ['/calendar/month', self::ROLE_USER];
        yield ['/calendar/month', self::ROLE_ADMIN];
        yield ['/calendar/month', self::ROLE_SUPER_ADMIN];
        yield ['/calendar/week', self::ROLE_USER];
        yield ['/calendar/week', self::ROLE_ADMIN];
        yield ['/calendar/week', self::ROLE_SUPER_ADMIN];
        yield ['/calendar/year', self::ROLE_USER];
        yield ['/calendar/year', self::ROLE_ADMIN];
        yield ['/calendar/year', self::ROLE_SUPER_ADMIN];

        yield ['/calendar/month/2024/100', self::ROLE_ADMIN, Response::HTTP_NOT_FOUND];
        yield ['/calendar/week/2024/100', self::ROLE_ADMIN, Response::HTTP_NOT_FOUND];
    }

    /**
     * @throws ORMException
     */
    public function testTwoCalculations(): void
    {
        $calculation1 = $this->getCalculation();
        $calculation2 = clone $calculation1;
        $this->addEntity($calculation1);
        $this->addEntity($calculation2);

        $this->checkRoute('/calendar/month', self::ROLE_ADMIN);
        $this->checkRoute('/calendar/week', self::ROLE_ADMIN);
        $this->checkRoute('/calendar/year', self::ROLE_ADMIN);

        $this->deleteEntitiesByClass(Calculation::class);
    }
}
