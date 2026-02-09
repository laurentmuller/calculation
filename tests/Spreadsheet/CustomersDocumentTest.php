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

namespace App\Tests\Spreadsheet;

use App\Controller\AbstractController;
use App\Entity\Customer;
use App\Spreadsheet\CustomersDocument;
use PHPUnit\Framework\TestCase;

final class CustomersDocumentTest extends TestCase
{
    public function testRender(): void
    {
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

        $controller = self::createStub(AbstractController::class);
        $document = new CustomersDocument($controller, [$customer1, $customer2, $customer3]);
        $actual = $document->render();
        self::assertTrue($actual);
    }
}
