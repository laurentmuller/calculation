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
use App\Service\SymfonyInfoService;
use App\Spreadsheet\SymfonyDocument;
use App\Tests\KernelServiceTestCase;

class SymfonyDocumentTest extends KernelServiceTestCase
{
    public function testRender(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $service = $this->getService(SymfonyInfoService::class);
        $doc = new SymfonyDocument($controller, $service);
        $actual = $doc->render();
        self::assertTrue($actual);
    }
}
