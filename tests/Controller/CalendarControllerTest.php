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
use Symfony\Component\HttpFoundation\Response;

final class CalendarControllerTest extends ControllerTestCase
{
    use CalculationTrait;

    #[\Override]
    public static function getRoutes(): \Generator
    {
        // valid
        foreach (['week', 'month', 'year'] as $suffix) {
            foreach ([self::ROLE_USER, self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN] as $role) {
                yield ['/calendar/' . $suffix, $role];
            }
        }

        // invalid week
        yield ['/calendar/week/2024/100', self::ROLE_ADMIN, Response::HTTP_NOT_FOUND];

        // invalid month
        yield ['/calendar/month/2024/100', self::ROLE_ADMIN, Response::HTTP_NOT_FOUND];
    }

    public function testTwoCalculations(): void
    {
        $calculation1 = $this->getCalculation();
        $calculation2 = clone $calculation1;
        $this->addEntity($calculation1);
        $this->addEntity($calculation2);

        $this->checkRoute('/calendar/week', self::ROLE_ADMIN);
        $this->checkRoute('/calendar/month', self::ROLE_ADMIN);
        $this->checkRoute('/calendar/year', self::ROLE_ADMIN);

        $this->deleteEntitiesByClass(Calculation::class);
    }
}
