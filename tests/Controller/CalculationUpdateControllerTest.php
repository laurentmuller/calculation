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

use App\Controller\CalculationUpdateController;
use App\Tests\EntityTrait\CalculationTrait;
use App\Tests\EntityTrait\ProductTrait;
use Doctrine\ORM\Exception\ORMException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(CalculationUpdateController::class)]
class CalculationUpdateControllerTest extends ControllerTestCase
{
    use CalculationTrait;
    use ProductTrait;

    public static function getRoutes(): \Iterator
    {
        yield ['/admin/update', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/admin/update', self::ROLE_ADMIN];
    }

    /**
     * @throws ORMException
     * @throws \Exception
     */
    public function testUpdate(): void
    {
        $calculation = $this->getCalculation();
        $data = [
            'form[dateFrom]' => '2024-06-01',
            'form[dateTo]' => '2024-07-01',
            'form[states][0]' => $calculation->getState()?->getId(),
            'form[simulate]' => '1',
            'form[confirm]' => '1',
        ];
        $this->checkForm(
            '/admin/update',
            'calculation.update.submit',
            $data,
            followRedirect: false
        );
    }
}
