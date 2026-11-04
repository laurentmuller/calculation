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

use App\Entity\Customer;
use App\Interfaces\DocumentHelperInterface;
use App\Spreadsheet\CustomersDocument;
use PHPUnit\Framework\TestCase;

final class CustomersDocumentTest extends TestCase
{
    public function testRender(): void
    {
        $customer1 = new Customer();
        $customer1->withFirstName('First Name')
            ->withLastName('A Last Name')
            ->withEmail('email@email.com');

        $customer2 = new Customer();
        $customer2->withFirstName('First Name')
            ->withLastName('B Last Name')
            ->withEmail('email@email.com');

        $customer3 = new Customer();
        $customer3->withFirstName('')
            ->withLastName('')
            ->withEmail('email@email.com');

        $helper = self::createStub(DocumentHelperInterface::class);
        $document = new CustomersDocument($helper, [$customer1, $customer2, $customer3]);
        $actual = $document->render();
        self::assertTrue($actual);
    }
}
