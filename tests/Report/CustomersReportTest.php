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

namespace App\Tests\Report;

use App\Controller\AbstractController;
use App\Entity\Customer;
use App\Report\CustomersReport;
use PHPUnit\Framework\TestCase;

final class CustomersReportTest extends TestCase
{
    public function testRenderGrouped(): void
    {
        $controller = self::createStub(AbstractController::class);
        $customer1 = new Customer();
        $customer1->setFirstName('First Name')
            ->setLastName('A Last Name')
            ->setEmail('email@email.com');

        $customer2 = new Customer();
        $customer2->setFirstName('First Name')
            ->setLastName('B Last Name')
            ->setEmail('email@email.com');

        $customer3 = new Customer();
        $customer3->setFirstName('')
            ->setLastName('')
            ->setEmail('email@email.com');

        $report = new CustomersReport($controller, [$customer1, $customer2, $customer3]);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderNotGrouped(): void
    {
        $controller = self::createStub(AbstractController::class);
        $customer = new Customer();
        $customer->setFirstName('First Name')
            ->setLastName('Last Name')
            ->setEmail('email@email.com');

        $report = new CustomersReport($controller, [$customer], false);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
