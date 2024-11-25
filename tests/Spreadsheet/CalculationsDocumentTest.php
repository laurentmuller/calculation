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
use App\Entity\Calculation;
use App\Spreadsheet\CalculationsDocument;
use App\Tests\Entity\IdTrait;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class CalculationsDocumentTest extends TestCase
{
    use IdTrait;

    /**
     * @throws Exception
     * @throws \ReflectionException
     */
    public function testRender(): void
    {
        $calculation = new Calculation();
        $calculation->setCustomer('Customer')
            ->setDescription('Description');
        self::setId($calculation);

        $controller = $this->createMock(AbstractController::class);
        $document = new CalculationsDocument($controller, [$calculation]);
        $actual = $document->render();
        self::assertTrue($actual);
    }
}
